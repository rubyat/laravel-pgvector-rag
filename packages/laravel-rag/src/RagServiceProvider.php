<?php

namespace RagStarter;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use RagStarter\Contracts\ChatDriver;
use RagStarter\Contracts\EmbeddingDriver;
use RagStarter\Drivers\FakeChatDriver;
use RagStarter\Drivers\FakeEmbeddingDriver;
use RagStarter\Drivers\OpenAiChatDriver;
use RagStarter\Drivers\OpenAiEmbeddingDriver;
use RagStarter\Ingestion\DocumentChunker;

class RagServiceProvider extends ServiceProvider
{
    /**
     * Register package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/rag.php', 'rag');

        $this->app->singleton(EmbeddingDriver::class, fn (Application $app) => $this->makeEmbeddingDriver($app));

        $this->app->singleton(ChatDriver::class, fn (Application $app) => $this->makeChatDriver($app));

        $this->app->bind(DocumentChunker::class, fn (Application $app) => new DocumentChunker(
            (int) $app['config']->get('rag.chunk_size', 1000),
            (int) $app['config']->get('rag.chunk_overlap', 200),
        ));
    }

    private function registerRoutes(): void
    {
        if (! config('rag.register_routes', true)) {
            return;
        }

        Route::group([
            'prefix' => config('rag.route_prefix', 'api/rag'),
            'middleware' => config('rag.middleware', ['api']),
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        });
    }

    private function makeEmbeddingDriver(Application $app): EmbeddingDriver
    {
        $config = $app['config'];
        $dimensions = (int) $config->get('rag.dimensions', 1536);

        return match ($config->get('rag.embedding_driver')) {
            'fake' => new FakeEmbeddingDriver($dimensions),
            default => new OpenAiEmbeddingDriver(
                apiKey: $config->get('rag.openai.api_key'),
                baseUri: $config->get('rag.openai.base_uri'),
                model: $config->get('rag.openai.embedding_model'),
                dimensions: $dimensions,
                timeout: (int) $config->get('rag.openai.timeout', 30),
            ),
        };
    }

    private function makeChatDriver(Application $app): ChatDriver
    {
        $config = $app['config'];

        return match ($config->get('rag.chat_driver')) {
            'fake' => new FakeChatDriver,
            default => new OpenAiChatDriver(
                apiKey: $config->get('rag.openai.api_key'),
                baseUri: $config->get('rag.openai.base_uri'),
                model: $config->get('rag.openai.chat_model'),
                timeout: (int) $config->get('rag.openai.timeout', 30),
            ),
        };
    }

    /**
     * Bootstrap package services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->registerRoutes();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/rag.php' => config_path('rag.php'),
            ], 'rag-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'rag-migrations');
        }
    }
}
