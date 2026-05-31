<?php

namespace RagStarter\Drivers;

use RagStarter\Contracts\EmbeddingDriver;

/**
 * A deterministic, network-free embedding driver for local use and tests.
 *
 * It uses a hashing vectorizer (bag-of-words into a fixed number of buckets)
 * and L2-normalizes the result. Texts that share words land closer together
 * under cosine similarity, which makes retrieval behaviour testable without
 * calling a real embedding API.
 */
class FakeEmbeddingDriver implements EmbeddingDriver
{
    public function __construct(private int $dimensions = 1536) {}

    public function embed(string $text): array
    {
        $vector = array_fill(0, $this->dimensions, 0.0);

        foreach ($this->tokenize($text) as $token) {
            $bucket = crc32($token) % $this->dimensions;
            $vector[$bucket] += 1.0;
        }

        return $this->normalize($vector);
    }

    public function embedBatch(array $texts): array
    {
        return array_map(fn (string $text) => $this->embed($text), array_values($texts));
    }

    public function dimensions(): int
    {
        return $this->dimensions;
    }

    /**
     * @return array<int, string>
     */
    private function tokenize(string $text): array
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY);

        return $words === false ? [] : $words;
    }

    /**
     * @param  array<int, float>  $vector
     * @return array<int, float>
     */
    private function normalize(array $vector): array
    {
        $magnitude = sqrt(array_sum(array_map(static fn ($v) => $v * $v, $vector)));

        if ($magnitude == 0.0) {
            return $vector;
        }

        return array_map(static fn ($v) => $v / $magnitude, $vector);
    }
}
