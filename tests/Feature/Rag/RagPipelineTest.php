<?php

use Rubyat\LaravelRag\Contracts\EmbeddingDriver;
use Rubyat\LaravelRag\Models\Document;
use Rubyat\LaravelRag\Rag\RagPipeline;

function seedChunk(string $source, int $index, string $content): void
{
    Document::create([
        'source' => $source,
        'chunk_index' => $index,
        'content' => $content,
        'embedding' => app(EmbeddingDriver::class)->embed($content),
    ]);
}

it('answers grounded in retrieved context with citations', function () {
    seedChunk('handbook', 0, 'Postgres pgvector stores embeddings and supports cosine distance search.');
    seedChunk('handbook', 1, 'Bread is made from flour, water, salt and yeast.');

    $result = app(RagPipeline::class)->ask('How does postgres pgvector search work?', 1);

    expect($result['citations'])->toHaveCount(1);
    expect($result['citations'][0]['source'])->toBe('handbook');
    expect($result['citations'][0]['chunk_index'])->toBe(0);
    expect($result['citations'][0])->toHaveKeys(['source', 'chunk_index', 'content', 'score']);
    expect($result['answer'])->toContain('Based on 1 source');
    expect($result['answer'])->toContain('pgvector');
});

it('returns a fallback answer and no citations when nothing is ingested', function () {
    $result = app(RagPipeline::class)->ask('anything?');

    expect($result['citations'])->toBe([]);
    expect($result['answer'])->toContain("don't have enough information");
});

it('exposes the ask endpoint returning answer and citations', function () {
    seedChunk('faq', 0, 'The ingestion endpoint chunks text and stores pgvector embeddings.');

    $response = $this->postJson('/api/rag/ask', [
        'question' => 'What does the ingestion endpoint do?',
        'top_k' => 1,
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'answer',
            'citations' => [['source', 'chunk_index', 'content', 'score']],
        ])
        ->assertJsonPath('citations.0.source', 'faq');
});

it('validates that a question is required', function () {
    $this->postJson('/api/rag/ask', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['question']);
});
