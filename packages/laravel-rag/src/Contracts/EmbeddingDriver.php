<?php

namespace RagStarter\Contracts;

interface EmbeddingDriver
{
    /**
     * Embed a single piece of text into a vector.
     *
     * @return array<int, float>
     */
    public function embed(string $text): array;

    /**
     * Embed many pieces of text at once.
     *
     * @param  array<int, string>  $texts
     * @return array<int, array<int, float>>
     */
    public function embedBatch(array $texts): array;

    /**
     * The dimensionality of the vectors this driver produces.
     */
    public function dimensions(): int;
}
