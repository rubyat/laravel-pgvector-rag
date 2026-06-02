<?php

namespace Rubyat\LaravelRag\Drivers;

use Illuminate\Support\Facades\Http;
use Rubyat\LaravelRag\Contracts\ChatDriver;
use RuntimeException;

class OpenAiChatDriver implements ChatDriver
{
    public function __construct(
        private ?string $apiKey,
        private string $baseUri,
        private string $model,
        private int $timeout = 30,
    ) {}

    public function answer(string $question, array $context): string
    {
        if (blank($this->apiKey)) {
            throw new RuntimeException('OPENAI_API_KEY is not set. Configure it in your .env to use the OpenAI chat driver.');
        }

        $response = Http::withToken($this->apiKey)
            ->timeout($this->timeout)
            ->baseUrl(rtrim($this->baseUri, '/'))
            ->post('/chat/completions', [
                'model' => $this->model,
                'temperature' => 0.2,
                'messages' => [
                    ['role' => 'system', 'content' => $this->systemPrompt()],
                    ['role' => 'user', 'content' => $this->userPrompt($question, $context)],
                ],
            ])
            ->throw()
            ->json();

        return trim($response['choices'][0]['message']['content'] ?? '');
    }

    private function systemPrompt(): string
    {
        return 'You are a helpful assistant. Answer the user\'s question using ONLY the '
            .'provided context. If the context does not contain the answer, say you do '
            .'not have enough information. Be concise.';
    }

    /**
     * @param  array<int, string>  $context
     */
    private function userPrompt(string $question, array $context): string
    {
        $joined = $context === []
            ? '(no context found)'
            : collect($context)
                ->map(fn (string $chunk, int $i) => '['.($i + 1)."] {$chunk}")
                ->implode("\n\n");

        return "Context:\n{$joined}\n\nQuestion: {$question}";
    }
}
