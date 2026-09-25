<?php
namespace AmirKateb\AiCoreClient\Value;
final readonly class SseEvent
{
    public function __construct(
        public ?string $id,
        public string $event,
        public mixed $data,
        public string $rawData,
    ) {}
}
