<?php

namespace Rubyat\LaravelRag\Drivers;

use Illuminate\Support\Facades\Http;
use Rubyat\LaravelRag\Contracts\EmbeddingDriver;
use RuntimeException;

class OpenAiEmbeddingDriver implements EmbeddingDriver
{
    public function __construct(
        private ?string $apiKey,
        private string $baseUri,
        private string $model,
        private int $dimensions,
        private int $timeout = 30,
    ) {}

    public function embed(string $text): array
    {
        return $this->embedBatch([$text])[0];
    }

    public function embedBatch(array $texts): array
    {
        if (blank($this->apiKey)) {
            throw new RuntimeException('OPENAI_API_KEY is not set. Configure it in your .env to use the OpenAI embedding driver.');
        }

        $response = Http::withToken($this->apiKey)
            ->timeout($this->timeout)
            ->baseUrl(rtrim($this->baseUri, '/'))
            ->post('/embeddings', [
                'model' => $this->model,
                'input' => array_values($texts),
            ])
            ->throw()
            ->json();

        return array_map(
            static fn (array $item) => array_map('floatval', $item['embedding']),
            $response['data'],
        );
    }

    public function dimensions(): int
    {
        return $this->dimensions;
    }
}
