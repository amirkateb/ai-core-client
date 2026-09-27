<?php

namespace AmirKateb\AiCoreClient\Laravel;

use AmirKateb\AiCoreClient\Client;
use Illuminate\Support\ServiceProvider;

class AiCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $configPath = dirname(__DIR__, 2).'/config/ai-core.php';
        $this->mergeConfigFrom($configPath, 'ai-core');

        $this->app->singleton(Client::class, function ($app): Client {
            $config = (array) $app['config']->get('ai-core', []);
            $client = new Client(
                (string) ($config['url'] ?? 'https://ai.katebsaber.ir'),
                (string) ($config['key'] ?? ''),
                (int) ($config['timeout'] ?? 300),
                (int) ($config['connect_timeout'] ?? 10)
            );

            $origin = trim((string) ($config['origin'] ?? ''));
            return $origin !== '' ? $client->withOrigin($origin) : $client;
        });
        $this->app->alias(Client::class, 'ai-core');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                dirname(__DIR__, 2).'/config/ai-core.php' => config_path('ai-core.php'),
            ], 'ai-core-config');
        }
    }
}
