<?php
namespace AmirKateb\AiCoreClient\Exception;
class ApiException extends AiCoreException
{
    public function __construct(
        public readonly int $statusCode,
        public readonly ?string $errorCode,
        string $message,
        public readonly array $response = [],
    ) { parent::__construct($message, $statusCode); }
}
