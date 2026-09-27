<?php

namespace AmirKateb\AiCoreClient;

final class BatchHandle
{
    public Client $client;
    public string $id;
    public array $submitted;

    public function __construct(Client $client, string $id, array $submitted)
    {
        $this->client = $client;
        $this->id = $id;
        $this->submitted = $submitted;
    }

    public function get(): array { return $this->client->batch($this->id); }
    public function cancel(): array { return $this->client->cancelBatch($this->id); }
    public function wait(int $timeoutSeconds = 600, float $pollSeconds = 1.5): array { return $this->client->waitForBatch($this->id, $timeoutSeconds, $pollSeconds); }
}
