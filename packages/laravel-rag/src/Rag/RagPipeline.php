<?php

namespace RagStarter\Rag;

use RagStarter\Contracts\ChatDriver;
use RagStarter\Retrieval\VectorRetriever;

/**
 * The end-to-end Retrieval-Augmented Generation flow:
 * retrieve the most similar chunks, then ask the chat driver to answer the
 * question grounded in those chunks, returning the answer plus citations.
 */
class RagPipeline
{
    public function __construct(
        private VectorRetriever $retriever,
        private ChatDriver $chat,
    ) {}

    /**
     * @return array{answer: string, citations: array<int, array{source: string, chunk_index: int, content: string, score: float}>}
     */
    public function ask(string $question, ?int $topK = null): array
    {
        $chunks = $this->retriever->search($question, $topK);

        $answer = $this->chat->answer($question, $chunks->pluck('content')->all());

        return [
            'answer' => $answer,
            'citations' => $chunks->map(fn ($doc) => [
                'source' => $doc->source,
                'chunk_index' => (int) $doc->chunk_index,
                'content' => $doc->content,
                'score' => round((float) $doc->similarity, 4),
            ])->all(),
        ];
    }
}
