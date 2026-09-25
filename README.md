# KatebSaber AI Core PHP SDK

Official PHP client for the AI Core Gateway.

```bash
composer require amirkateb/ai-core-client
```

```php
use AmirKateb\AiCoreClient\Client;

$ai = new Client('https://ai.katebsaber.ir', 'aik_...');
$request = $ai->chat([
    'model' => 'ollama:qwen3.5:4b',
    'message' => 'سلام',
], Client::idempotencyKey());
$result = $request->wait();
```

The SDK exposes every public `/api/v1` capability, request/batch lifecycle operations, resumable SSE, private media download, idempotency helpers and webhook signature verification.
