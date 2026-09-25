<?php
namespace AmirKateb\AiCoreClient;
use AmirKateb\AiCoreClient\Value\SseEvent;
final readonly class RequestHandle
{
    public function __construct(public Client $client,public string $id,public array $submitted){}
    public function get():array{return $this->client->request($this->id);}public function cancel():array{return $this->client->cancelRequest($this->id);}public function retry():self{return $this->client->retryRequest($this->id);}public function wait(int $timeoutSeconds=300,float $pollSeconds=1.0):array{return $this->client->waitForRequest($this->id,$timeoutSeconds,$pollSeconds);}public function stream(callable $listener,int $after=0):void{$this->client->streamRequest($this->id,$listener,$after);}public function downloadMedia(string $target):string{return $this->client->downloadMedia($this->id,$target);}
}
