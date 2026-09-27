# AI Core PHP SDK · v1.0.0

> Official PHP client for **KatebSaber AI Core Gateway** — one durable API for multi-provider AI, queues, streaming, media, observability and **Auto Switch failover profiles**.

**Laravel 8 → 13** · **PHP 8.0+** · Framework-agnostic · cURL transport · Zero runtime dependency on a specific AI provider

---

## Why this SDK?

Your application talks to one stable contract while AI Core handles provider routing, Xray/proxy policy, queues, retries, usage, logs and failover behind the gateway.

- Chat, Text, Responses, Vision, OCR, Image, Audio, Embeddings, Moderation, Rerank, Document and Video APIs
- Durable async requests with `RequestHandle`
- Resumable SSE with `Last-Event-ID`
- Batch requests and signed webhooks
- Per-company model allowlists and quotas
- **Auto Switch profiles with ordered provider/model failover**
- Full profile discovery, including every model inside a profile
- Laravel 8–13 auto-discovery, container binding and `AiCore` facade
- Plain PHP usage with no Laravel dependency

## Install

```bash
composer require amirkateb/ai-core-client:^1.0
```

Requirements: PHP `>=8.0 <9.0`, `ext-curl`, `ext-json` and Composer 2.

## 60-second start

```php
<?php

use AmirKateb\AiCoreClient\Client;

$ai = new Client(
    'https://ai.katebsaber.ir',
    getenv('AI_CORE_API_KEY'),
);

$request = $ai->chat([
    'model' => 'ollama:qwen2.5-coder:3b',
    'message' => 'سلام، پاسخ کوتاه بده.',
], Client::idempotencyKey());

$result = $request->wait();

echo $result['result']['content'] ?? '';
```

## Auto Switch profiles

An Auto Switch profile is an ordered chain of 2–25 provider/model candidates. The gateway starts with candidate #1 and moves forward only when that candidate fails. Provider proxy rules are never bypassed: a provider configured for Xray stays fail-closed on Xray, then the profile advances to the next candidate.

### Discover profiles and all models inside them

```php
$profiles = $ai->autoSwitchProfiles('chat');

foreach ($profiles['data'] as $profile) {
    echo $profile['name'].' — '.$profile['model_count']." models\n";

    foreach ($profile['models'] as $candidate) {
        printf(
            "#%d %s / %s | available=%s | direct_access=%s\n",
            $candidate['position'],
            $candidate['provider'],
            $candidate['model'],
            $candidate['available'] ? 'yes' : 'no',
            $candidate['direct_access'] ? 'yes' : 'no',
        );
    }
}
```

`direct_access=false` is intentional and important: if the company is allowed to use the **profile** but not an underlying model directly, that model can still run **inside the profile only**. Calling that same model directly with `provider:model` remains forbidden.

### Run Chat through a profile

```php
$profileId = 'f667da3d-b798-453a-9d68-1b03ed13a789';

$request = $ai->chatWithProfile($profileId, [
    'message' => 'این درخواست را با زنجیره اضطراری اجرا کن.',
], Client::idempotencyKey('failover'));

$result = $request->wait();

// The requested model remains the profile identity:
echo $result['model']; // auto_switch:<profile_uuid>

// The actual winning provider/model is explicit:
echo $result['resolved_model'];

// Full failover diagnostics:
print_r($result['auto_switch']['attempts'] ?? []);
```

The same profile can be used for buffered text generation:

```php
$request = $ai->textWithProfile($profileId, [
    'text' => 'یک عنوان کوتاه بساز.',
]);
```

### Stream a failover request

```php
use AmirKateb\AiCoreClient\Value\SseEvent;

$request = $ai->chatStreamWithProfile($profileId, [
    'message' => 'مرحله‌ای توضیح بده.',
]);

$request->stream(function (SseEvent $event): void {
    if ($event->event === 'attempt_start') {
        echo "Trying {$event->data['provider']} / {$event->data['model']}\n";
    }

    if ($event->event === 'attempt_failed') {
        echo "Candidate failed, switching…\n";
    }

    if ($event->event === 'delta') {
        echo $event->data['text'] ?? '';
    }
});
```

Auto Switch streaming is durable SSE at the gateway boundary. Provider candidates may be executed buffered when required by their managed proxy transport; the client still receives attempt events and the final response through the same resumable request stream.

## Model discovery

```php
$all = $ai->models();
$chat = $ai->models('chat');
$profile = $ai->autoSwitchProfile($profileId, 'chat');
$capabilities = $ai->capabilities();
$health = $ai->health();
```

`models()` includes individually allowed models plus any Auto Switch profiles explicitly allowed for the authenticated company.

## Laravel 8–13

The package is auto-discovered by Laravel. No manual service provider registration is required.

```env
AI_CORE_URL=https://ai.katebsaber.ir
AI_CORE_API_KEY=aik_...
AI_CORE_TIMEOUT=300
AI_CORE_CONNECT_TIMEOUT=10
# Optional when the API key is tied to a Site Origin policy:
AI_CORE_ORIGIN=https://app.example.com
```

Publish the config when you want an application-level file:

```bash
php artisan vendor:publish --tag=ai-core-config
```

### Dependency injection

```php
use AmirKateb\AiCoreClient\Client;

final class SummarizeArticle
{
    public function __construct(private Client $ai) {}

    public function __invoke(string $text): array
    {
        return $this->ai->chat([
            'message' => 'خلاصه کن: '.$text,
        ])->wait();
    }
}
```

### Facade

```php
use AiCore;

$result = AiCore::chat([
    'message' => 'سلام',
])->wait();
```

The integration uses APIs shared across Laravel 8, 9, 10, 11, 12 and 13. The SDK itself does not require `illuminate/*`, so plain PHP and non-Laravel frameworks remain first-class.

## Request lifecycle

```php
$handle = $ai->chat(['message' => 'Hello']);

$handle->get();            // Current state
$handle->wait();           // Poll until terminal
$handle->stream($listener); // Resumable SSE
$handle->cancel();         // Cancel queued/retry/processing
$retry = $handle->retry(); // New request from a failed/cancelled request
```

Terminal states are `success`, `failed` and `cancelled`.

## Files and media

```php
$vision = $ai->visionAnalyze(
    __DIR__.'/invoice.jpg',
    ['model' => 'provider:vision-model', 'prompt' => 'فاکتور را تحلیل کن'],
);

$result = $vision->wait();
```

For generated media:

```php
$image = $ai->imageGeneration([
    'model' => 'provider:image-model',
    'prompt' => 'A minimal AI gateway icon',
]);

$image->wait();
$image->downloadMedia(__DIR__.'/output.webp');
```

## Batch

```php
$batch = $ai->batchBuilder()
    ->metadata(['job' => 'nightly-enrichment'])
    ->chat('provider:model', ['message' => 'خلاصه کن: ...'], 'item_1')
    ->chat('auto_switch:'.$profileId, ['message' => 'با failover خلاصه کن: ...'], 'item_2')
    ->embeddings('provider:embedding-model', ['input' => ['متن اول', 'متن دوم']], 'item_3')
    ->send(Client::idempotencyKey('batch'));

$result = $batch->wait();
```

## Webhooks

```php
use AmirKateb\AiCoreClient\WebhookVerifier;

$payload = WebhookVerifier::fromHeaders(
    getenv('AI_CORE_WEBHOOK_SECRET'),
    getallheaders(),
    file_get_contents('php://input'),
);
```

Webhook request payloads expose `model`, `resolved_model`, and `auto_switch` details when a failover profile was used.

## Error handling

```php
use AmirKateb\AiCoreClient\Exception\ApiException;
use AmirKateb\AiCoreClient\Exception\TransportException;

try {
    $result = $ai->chat(['message' => 'سلام'])->wait();
} catch (ApiException $e) {
    error_log('HTTP '.$e->statusCode.' / '.($e->errorCode ?? 'unknown'));
    error_log($e->getMessage());
} catch (TransportException $e) {
    error_log('Network error: '.$e->getMessage());
}
```

## Public SDK methods

| Area | Methods |
|---|---|
| Discovery | `status`, `health`, `models`, `capabilities`, `usage`, `limits` |
| Auto Switch | `autoSwitchProfiles`, `autoSwitchProfile`, `chatWithProfile`, `chatStreamWithProfile`, `textWithProfile` |
| Text | `chat`, `chatStream`, `text`, `createResponse` |
| Image/Vision | `imageGeneration`, `imageEdit`, `visionAnalyze`, `ocr` |
| Audio | `audioAnalyze`, `speech`, `transcription`, `audioTranslation` |
| Data/Safety | `embeddings`, `moderation`, `rerank` |
| Document/Video | `documentAnalyze`, `videoAnalyze`, `videoGeneration` |
| Request lifecycle | `requests`, `request`, `cancelRequest`, `retryRequest`, `streamRequest`, `downloadMedia` |
| Batch | `batchBuilder`, `batches`, `createBatch`, `batch`, `cancelBatch` |

## Security model

- Keep API keys in backend secrets only.
- Auto Switch profile permission is independent from direct model permission.
- Allowing a profile **does not** expose its internal models for direct calls.
- Gateway re-checks profile permission when a queued request actually executes.
- Provider-specific Xray/proxy policy remains enforced for every candidate.
- Use idempotency keys for retryable business operations.
- Verify webhook signatures before side effects.

## Versioning

This package follows Semantic Versioning. `1.0.0` is the first stable contract containing Auto Switch profile discovery/execution and Laravel 8–13 integration.

---

**Package:** `amirkateb/ai-core-client`

**License:** MIT

**Gateway:** `https://ai.katebsaber.ir/api/v1`
