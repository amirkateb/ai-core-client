<?php
namespace AmirKateb\AiCoreClient\Http;
use AmirKateb\AiCoreClient\Exception\ApiException;
use AmirKateb\AiCoreClient\Exception\TransportException;
use AmirKateb\AiCoreClient\Value\SseEvent;
use CURLFile;
final class Transport
{
    private array $defaultHeaders = [];
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly int $timeoutSeconds = 300,
        private readonly int $connectTimeoutSeconds = 10,
    ) {
        if (trim($baseUrl) === '' || trim($apiKey) === '') throw new \InvalidArgumentException('Base URL and API key are required.');
    }
    public function withHeaders(array $headers): self { $clone=clone $this; $clone->defaultHeaders=array_merge($this->defaultHeaders,$headers); return $clone; }
    public function json(string $method,string $path,array $options=[]): array
    {
        $response=$this->request($method,$path,$options);
        if($response['body']==='') return [];
        $decoded=json_decode($response['body'],true);
        if(!is_array($decoded)) throw new TransportException('Gateway returned a non-JSON response.');
        return $decoded;
    }
    public function download(string $path,string $target,array $query=[]): string
    {
        $dir=dirname($target); if(!is_dir($dir)&&!@mkdir($dir,0775,true)&&!is_dir($dir)) throw new TransportException('Could not create target directory.');
        $fh=@fopen($target,'wb'); if(!$fh) throw new TransportException('Could not open target file.');
        $ch=$this->curl($path,$query); curl_setopt($ch,CURLOPT_FILE,$fh); curl_setopt($ch,CURLOPT_FOLLOWLOCATION,false);
        $status=0; try { if(curl_exec($ch)===false) throw new TransportException(curl_error($ch)); $status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE); } finally { curl_close($ch); fclose($fh); }
        if($status<200||$status>=300){@unlink($target);throw new ApiException($status,null,'Media download failed with HTTP '.$status);}
        return $target;
    }
    public function sse(string $path, callable $listener, int $after=0, array $query=[]): void
    {
        if($after>0)$query['after']=$after;
        $ch=$this->curl($path,$query,['Accept: text/event-stream','Last-Event-ID: '.($after>0?(string)$after:'')]);
        $buffer='';
        curl_setopt($ch,CURLOPT_WRITEFUNCTION,function($ch,string $chunk)use(&$buffer,$listener){$buffer.=$chunk;while(($pos=strpos($buffer,"\n\n"))!==false){$frame=substr($buffer,0,$pos);$buffer=substr($buffer,$pos+2);$event=$this->parseSseFrame($frame);if($event)$listener($event);}return strlen($chunk);});
        if(curl_exec($ch)===false){$error=curl_error($ch);curl_close($ch);throw new TransportException($error);}
        $status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);curl_close($ch);if($status<200||$status>=300)throw new ApiException($status,null,'SSE request failed with HTTP '.$status);
    }
    private function request(string $method,string $path,array $options): array
    {
        $headers=(array)($options['headers']??[]);$ch=$this->curl($path,(array)($options['query']??[]),$headers);curl_setopt($ch,CURLOPT_CUSTOMREQUEST,strtoupper($method));
        if(array_key_exists('json',$options)){curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($options['json'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));curl_setopt($ch,CURLOPT_HTTPHEADER,$this->headers(array_merge($headers,['Content-Type: application/json','Accept: application/json'])));}
        elseif(array_key_exists('multipart',$options)){curl_setopt($ch,CURLOPT_POSTFIELDS,$this->multipart((array)$options['multipart']));curl_setopt($ch,CURLOPT_HTTPHEADER,$this->headers(array_merge($headers,['Accept: application/json'])));}
        $raw=curl_exec($ch);if($raw===false){$error=curl_error($ch);curl_close($ch);throw new TransportException($error);}$status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);curl_close($ch);
        if($status<200||$status>=300){$json=json_decode((string)$raw,true);$error=is_array($json)?(array)($json['error']??[]):[];throw new ApiException($status,isset($error['code'])?(string)$error['code']:null,(string)($error['message']??('Gateway request failed with HTTP '.$status)),is_array($json)?$json:[]);}
        return ['status'=>$status,'body'=>(string)$raw];
    }
    private function curl(string $path,array $query=[],array $headers=[])
    {
        $url=rtrim($this->baseUrl,'/').'/'.ltrim($path,'/');if($query)$url.=(str_contains($url,'?')?'&':'?').http_build_query($query);
        $ch=curl_init($url);if($ch===false)throw new TransportException('Could not initialize cURL.');curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>$this->connectTimeoutSeconds,CURLOPT_TIMEOUT=>$this->timeoutSeconds,CURLOPT_HTTPHEADER=>$this->headers($headers),CURLOPT_ENCODING=>'']);return $ch;
    }
    private function headers(array $headers): array
    {
        $base=['Authorization: Bearer '.$this->apiKey,'User-Agent: AmirKateb-AI-Core-PHP/0.1.0'];foreach($this->defaultHeaders as $name=>$value)$base[]=$name.': '.$value;foreach($headers as $k=>$v)$base[]=is_int($k)?$v:$k.': '.$v;return array_values(array_filter($base,fn($h)=>trim((string)$h)!==''&&!str_ends_with((string)$h,': ')));
    }
    private function multipart(array $items): array
    {
        $payload=[];foreach($items as $name=>$value){if(is_array($value)&&isset($value['file'])){$path=(string)$value['file'];if(!is_file($path))throw new \InvalidArgumentException('File not found: '.$path);$payload[$name]=new CURLFile($path,$value['mime']??mime_content_type($path)?:'application/octet-stream',$value['name']??basename($path));}elseif(is_array($value)){$payload[$name]=json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);}elseif($value!==null){$payload[$name]=(string)$value;}}return $payload;
    }
    private function parseSseFrame(string $frame): ?SseEvent
    {
        $id=null;$event='message';$data=[];foreach(preg_split('/\r?\n/',$frame)?:[] as $line){if($line===''||str_starts_with($line,':'))continue;[$field,$value]=array_pad(explode(':',$line,2),2,'');$value=ltrim($value);if($field==='id')$id=$value;elseif($field==='event')$event=$value;elseif($field==='data')$data[]=$value;}$raw=implode("\n",$data);if($raw===''&&$event==='message'&&$id===null)return null;$decoded=json_decode($raw,true);return new SseEvent($id,$event,json_last_error()===JSON_ERROR_NONE?$decoded:$raw,$raw);
    }
}
