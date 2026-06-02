<?php

namespace Rubyat\LaravelRag\Ingestion;

use InvalidArgumentException;

/**
 * Splits a large body of text into overlapping, character-bounded chunks
 * suitable for embedding. Overlap preserves context across boundaries so a
 * sentence split between two chunks is still retrievable from either one.
 */
class DocumentChunker
{
    public function __construct(
        private int $chunkSize = 1000,
        private int $overlap = 200,
    ) {
        if ($this->chunkSize < 1) {
            throw new InvalidArgumentException('Chunk size must be at least 1.');
        }

        if ($this->overlap < 0 || $this->overlap >= $this->chunkSize) {
            throw new InvalidArgumentException('Overlap must be >= 0 and smaller than the chunk size.');
        }
    }

    /**
     * @return array<int, string>
     */
    public function chunk(string $text): array
    {
        $text = trim($text);

        if ($text === '') {
            return [];
        }

        $length = mb_strlen($text);

        if ($length <= $this->chunkSize) {
            return [$text];
        }

        $step = $this->chunkSize - $this->overlap;
        $chunks = [];

        for ($start = 0; $start < $length; $start += $step) {
            $chunk = trim(mb_substr($text, $start, $this->chunkSize));

            if ($chunk !== '') {
                $chunks[] = $chunk;
            }

            // The final window already reached the end of the text.
            if ($start + $this->chunkSize >= $length) {
                break;
            }
        }

        return $chunks;
    }
}
