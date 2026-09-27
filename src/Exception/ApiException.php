<?php

namespace AmirKateb\AiCoreClient\Exception;

class ApiException extends AiCoreException
{
    public int $statusCode;
    public ?string $errorCode;
    public array $response;

    public function __construct(int $statusCode, ?string $errorCode, string $message, array $response = [])
    {
        $this->statusCode = $statusCode;
        $this->errorCode = $errorCode;
        $this->response = $response;
        parent::__construct($message, $statusCode);
    }
}
