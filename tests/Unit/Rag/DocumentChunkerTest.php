<?php

use Rubyat\LaravelRag\Ingestion\DocumentChunker;

it('returns an empty array for blank input', function () {
    expect((new DocumentChunker(100, 20))->chunk('   '))->toBe([]);
});

it('returns a single chunk when text is shorter than the chunk size', function () {
    $chunks = (new DocumentChunker(100, 20))->chunk('short text');

    expect($chunks)->toBe(['short text']);
});

it('splits long text into overlapping chunks', function () {
    $text = str_repeat('a', 250);
    $chunks = (new DocumentChunker(100, 20))->chunk($text);

    // step = 80 => windows at 0..100, 80..180, 160..250 => 3 chunks
    expect($chunks)->toHaveCount(3);
    expect(mb_strlen($chunks[0]))->toBe(100);
    expect(mb_strlen($chunks[2]))->toBe(90); // remaining 160..250
});

it('produces overlapping content between consecutive chunks', function () {
    $text = collect(range(1, 60))->map(fn ($n) => "word{$n}")->implode(' ');
    $chunks = (new DocumentChunker(50, 10))->chunk($text);

    $tail = mb_substr($chunks[0], -10);
    expect(mb_substr($chunks[1], 0, 10))->toBe($tail);
});

it('handles multibyte text without splitting characters', function () {
    $text = str_repeat('আ', 120);
    $chunks = (new DocumentChunker(50, 10))->chunk($text);

    expect(implode('', array_map('mb_strlen', $chunks)))->not->toBeEmpty();
    foreach ($chunks as $chunk) {
        expect(mb_strlen($chunk))->toBeLessThanOrEqual(50);
    }
});

it('rejects an overlap larger than or equal to the chunk size', function () {
    new DocumentChunker(50, 50);
})->throws(InvalidArgumentException::class);
