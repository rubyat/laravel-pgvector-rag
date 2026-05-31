<?php

use RagStarter\Models\Document;

it('ingests a document into multiple embedded chunks', function () {
    $content = str_repeat('Postgres pgvector enables semantic search. ', 200);

    $response = $this->postJson('/api/rag/ingest', [
        'source' => 'guide.md',
        'content' => $content,
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['message', 'source', 'chunks'])
        ->assertJsonPath('source', 'guide.md');

    $chunks = Document::where('source', 'guide.md')->orderBy('chunk_index')->get();

    expect($chunks->count())->toBeGreaterThan(1);
    expect($chunks->first()->embedding)->toHaveCount(1536);
    expect($chunks->pluck('chunk_index')->all())->toBe(range(0, $chunks->count() - 1));
    expect($response->json('chunks'))->toBe($chunks->count());
});

it('stores a short document as a single chunk', function () {
    $this->postJson('/api/rag/ingest', [
        'source' => 'note',
        'content' => 'hello world',
    ])->assertCreated();

    expect(Document::where('source', 'note')->count())->toBe(1);
});

it('persists provided metadata', function () {
    $this->postJson('/api/rag/ingest', [
        'source' => 'note',
        'content' => 'hello world',
        'metadata' => ['url' => 'https://example.com'],
    ])->assertCreated();

    expect(Document::where('source', 'note')->first()->metadata)
        ->toBe(['url' => 'https://example.com']);
});

it('validates that source and content are required', function () {
    $this->postJson('/api/rag/ingest', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['source', 'content']);
});
