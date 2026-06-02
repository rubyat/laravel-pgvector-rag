<?php

use Rubyat\LaravelRag\Contracts\EmbeddingDriver;
use Rubyat\LaravelRag\Models\Document;
use Rubyat\LaravelRag\Retrieval\VectorRetriever;

function seedDocument(string $source, string $content): void
{
    Document::create([
        'source' => $source,
        'chunk_index' => 0,
        'content' => $content,
        'embedding' => app(EmbeddingDriver::class)->embed($content),
    ]);
}

beforeEach(function () {
    seedDocument('pets', 'cats and dogs are popular household pets');
    seedDocument('db', 'postgres vector database indexing and search');
    seedDocument('cooking', 'baking bread requires flour water and yeast');
});

it('returns the most similar chunk first', function () {
    $results = app(VectorRetriever::class)->search('vector database postgres search', 2);

    expect($results)->toHaveCount(2);
    expect($results->first()->source)->toBe('db');
    expect((float) $results->first()->similarity)
        ->toBeGreaterThan((float) $results->last()->similarity);
});

it('respects the requested top-k limit', function () {
    $results = app(VectorRetriever::class)->search('postgres', 1);

    expect($results)->toHaveCount(1);
});

it('falls back to the configured top_k when none is given', function () {
    config()->set('rag.top_k', 2);

    $results = app(VectorRetriever::class)->search('anything goes here');

    expect($results)->toHaveCount(2);
});

it('exposes a similarity score between 0 and 1', function () {
    $results = app(VectorRetriever::class)->search('postgres vector database', 3);

    foreach ($results as $doc) {
        expect((float) $doc->similarity)->toBeGreaterThanOrEqual(-0.000001)
            ->toBeLessThanOrEqual(1.000001);
    }
});
