<?php

namespace AmirKateb\AiCoreClient;

final class RequestHandle
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

    public function get(): array { return $this->client->request($this->id); }
    public function cancel(): array { return $this->client->cancelRequest($this->id); }
    public function retry(): self { return $this->client->retryRequest($this->id); }
    public function wait(int $timeoutSeconds = 300, float $pollSeconds = 1.0): array { return $this->client->waitForRequest($this->id, $timeoutSeconds, $pollSeconds); }
    public function stream(callable $listener, int $after = 0): void { $this->client->streamRequest($this->id, $listener, $after); }
    public function downloadMedia(string $target): string { return $this->client->downloadMedia($this->id, $target); }
}
