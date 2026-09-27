<?php

namespace AmirKateb\AiCoreClient\Laravel\Facades;

use AmirKateb\AiCoreClient\Client;
use Illuminate\Support\Facades\Facade;

class AiCore extends Facade
{
    protected static function getFacadeAccessor()
    {
        return Client::class;
    }
}
