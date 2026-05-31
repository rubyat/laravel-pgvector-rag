<?php

namespace RagStarter\Retrieval;

use Illuminate\Support\Collection;
use RagStarter\Contracts\EmbeddingDriver;
use RagStarter\Models\Document;

/**
 * Finds the chunks most similar to a query using pgvector cosine distance.
 * Each returned Document carries a "similarity" attribute in [0, 1] where
 * higher is closer (1 - cosine distance).
 */
class VectorRetriever
{
    public function __construct(private EmbeddingDriver $embedder) {}

    /**
     * @return Collection<int, Document>
     */
    public function search(string $query, ?int $topK = null): Collection
    {
        return $this->searchByVector($this->embedder->embed($query), $topK);
    }

    /**
     * @param  array<int, float>  $vector
     * @return Collection<int, Document>
     */
    public function searchByVector(array $vector, ?int $topK = null): Collection
    {
        $topK = $topK ?? (int) config('rag.top_k', 4);
        $literal = '['.implode(',', array_map(static fn ($v) => (float) $v, $vector)).']';

        return Document::query()
            ->select('*')
            ->selectRaw('1 - (embedding <=> ?::vector) as similarity', [$literal])
            ->orderByRaw('embedding <=> ?::vector', [$literal])
            ->limit($topK)
            ->get();
    }
}
