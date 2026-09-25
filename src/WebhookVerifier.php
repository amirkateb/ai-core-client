<?php
namespace AmirKateb\AiCoreClient;
use AmirKateb\AiCoreClient\Exception\AiCoreException;
final class WebhookVerifier
{
    public static function verify(string $secret,string $timestamp,string $rawBody,string $signature,int $toleranceSeconds=300): array
    {
        if(!ctype_digit($timestamp)||abs(time()-(int)$timestamp)>$toleranceSeconds)throw new AiCoreException('Webhook timestamp is outside the allowed tolerance.');$expected='sha256='.hash_hmac('sha256',$timestamp.'.'.$rawBody,$secret);if(!hash_equals($expected,trim($signature)))throw new AiCoreException('Webhook signature is invalid.');$payload=json_decode($rawBody,true,512,JSON_THROW_ON_ERROR);if(!is_array($payload))throw new AiCoreException('Webhook payload is invalid.');return $payload;
    }
    public static function fromHeaders(string $secret,array $headers,string $rawBody,int $toleranceSeconds=300):array{$norm=[];foreach($headers as $k=>$v)$norm[strtolower((string)$k)]=is_array($v)?(string)($v[0]??''):(string)$v;return self::verify($secret,$norm['x-ai-timestamp']??'',$rawBody,$norm['x-ai-signature']??'',$toleranceSeconds);}
}
