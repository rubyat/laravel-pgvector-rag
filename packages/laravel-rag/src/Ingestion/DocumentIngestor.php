<?php

namespace RagStarter\Ingestion;

use Illuminate\Support\Collection;
use RagStarter\Contracts\EmbeddingDriver;
use RagStarter\Models\Document;

/**
 * Turns a raw source document into stored, embedded chunks.
 */
class DocumentIngestor
{
    public function __construct(
        private DocumentChunker $chunker,
        private EmbeddingDriver $embedder,
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     * @return Collection<int, Document>
     */
    public function ingest(string $source, string $content, array $metadata = []): Collection
    {
        $chunks = $this->chunker->chunk($content);

        if ($chunks === []) {
            return collect();
        }

        $vectors = $this->embedder->embedBatch($chunks);

        return collect($chunks)->map(function (string $chunk, int $index) use ($source, $metadata, $vectors) {
            return Document::create([
                'source' => $source,
                'chunk_index' => $index,
                'content' => $chunk,
                'metadata' => $metadata === [] ? null : $metadata,
                'embedding' => $vectors[$index],
            ]);
        });
    }
}
