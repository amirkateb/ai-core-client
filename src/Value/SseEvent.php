<?php

namespace AmirKateb\AiCoreClient\Value;

final class SseEvent
{
    public ?string $id;
    public string $event;
    public $data;
    public string $rawData;

    public function __construct(?string $id, string $event, $data, string $rawData)
    {
        $this->id = $id;
        $this->event = $event;
        $this->data = $data;
        $this->rawData = $rawData;
    }
}
