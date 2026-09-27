<?php

return [
    'url' => env('AI_CORE_URL', 'https://ai.katebsaber.ir'),
    'key' => env('AI_CORE_API_KEY'),
    'timeout' => (int) env('AI_CORE_TIMEOUT', 300),
    'connect_timeout' => (int) env('AI_CORE_CONNECT_TIMEOUT', 10),
    'origin' => env('AI_CORE_ORIGIN'),
];
