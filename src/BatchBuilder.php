<?php
namespace AmirKateb\AiCoreClient;
final class BatchBuilder
{
    private array $items=[];private array $metadata=[];
    public function __construct(private readonly Client $client){}
    public function metadata(array $metadata):self{$clone=clone $this;$clone->metadata=$metadata;return$clone;}
    public function chat(string $model,array $payload,?string $clientRequestId=null):self{return$this->add('chat',$model,$payload,$clientRequestId);}public function text(string $model,array $payload,?string $clientRequestId=null):self{return$this->add('text',$model,$payload,$clientRequestId);}public function response(string $model,array $payload,?string $clientRequestId=null):self{return$this->add('responses',$model,$payload,$clientRequestId);}public function embeddings(string $model,array $payload,?string $clientRequestId=null):self{return$this->add('embeddings',$model,$payload,$clientRequestId);}public function moderation(string $model,array $payload,?string $clientRequestId=null):self{return$this->add('moderation',$model,$payload,$clientRequestId);}public function rerank(string $model,array $payload,?string $clientRequestId=null):self{return$this->add('rerank',$model,$payload,$clientRequestId);}
    public function send(?string $idempotencyKey=null):BatchHandle{return$this->client->createBatch($this->items,$this->metadata,$idempotencyKey);}public function items():array{return$this->items;}
    private function add(string $operation,string $model,array $payload,?string $clientRequestId):self{$clone=clone $this;$item=['operation'=>$operation,'model'=>$model,'payload'=>$payload];if($clientRequestId!==null)$item['client_request_id']=$clientRequestId;$clone->items[]=$item;return$clone;}
}
