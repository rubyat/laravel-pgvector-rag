<?php

use RagStarter\Drivers\FakeEmbeddingDriver;

function cosine(array $a, array $b): float
{
    $dot = 0.0;
    foreach ($a as $i => $v) {
        $dot += $v * $b[$i];
    }

    return $dot; // vectors are already L2-normalized
}

it('produces vectors of the configured dimension', function () {
    $driver = new FakeEmbeddingDriver(128);

    expect($driver->embed('hello world'))->toHaveCount(128);
    expect($driver->dimensions())->toBe(128);
});

it('is deterministic for the same input', function () {
    $driver = new FakeEmbeddingDriver(128);

    expect($driver->embed('the quick brown fox'))
        ->toBe($driver->embed('the quick brown fox'));
});

it('places texts sharing words closer than unrelated texts', function () {
    $driver = new FakeEmbeddingDriver(256);

    $base = $driver->embed('postgres vector database search');
    $similar = $driver->embed('vector database search engine');
    $unrelated = $driver->embed('completely different unrelated topic about cooking');

    expect(cosine($base, $similar))->toBeGreaterThan(cosine($base, $unrelated));
});

it('embeds a batch preserving order', function () {
    $driver = new FakeEmbeddingDriver(64);
    $batch = $driver->embedBatch(['one', 'two']);

    expect($batch)->toHaveCount(2);
    expect($batch[0])->toBe($driver->embed('one'));
    expect($batch[1])->toBe($driver->embed('two'));
});
