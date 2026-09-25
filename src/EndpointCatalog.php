<?php
namespace AmirKateb\AiCoreClient;
final class EndpointCatalog
{
    public const ENDPOINTS=[
        'GET /status','GET /health','GET /usage','GET /limits','GET /models','GET /capabilities',
        'POST /chat','POST /chat/stream','POST /text','POST /images/generations','POST /images/edits','POST /vision/analyze','POST /ocr','POST /audio/analyze','POST /audio/speech','POST /embeddings','POST /audio/transcriptions','POST /audio/translations','POST /moderations','POST /rerank','POST /documents/analyze','POST /video/analyze','POST /videos/generations','POST /responses',
        'GET /batches','POST /batches','GET /batches/{batchId}','POST /batches/{batchId}/cancel',
        'GET /requests','GET /requests/{requestId}','POST /requests/{requestId}/cancel','POST /requests/{requestId}/retry','GET /requests/{requestId}/stream','GET /requests/{requestId}/media',
    ];
}
