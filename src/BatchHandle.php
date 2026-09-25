<?php
namespace AmirKateb\AiCoreClient;
final readonly class BatchHandle
{
    public function __construct(public Client $client,public string $id,public array $submitted){}
    public function get():array{return $this->client->batch($this->id);}public function cancel():array{return $this->client->cancelBatch($this->id);}public function wait(int $timeoutSeconds=600,float $pollSeconds=1.5):array{return $this->client->waitForBatch($this->id,$timeoutSeconds,$pollSeconds);}
}
