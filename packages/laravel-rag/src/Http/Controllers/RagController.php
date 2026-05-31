<?php

namespace RagStarter\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use RagStarter\Http\Requests\AskRequest;
use RagStarter\Http\Requests\IngestRequest;
use RagStarter\Ingestion\DocumentChunker;
use RagStarter\Ingestion\DocumentIngestor;
use RagStarter\Ingestion\IngestDocumentJob;
use RagStarter\Rag\RagPipeline;

class RagController extends Controller
{
    /**
     * Chunk, embed and store a source document.
     *
     * Ingests inline by default; dispatches IngestDocumentJob to the queue when
     * rag.queue_ingestion is enabled (requires a running worker).
     */
    public function ingest(
        IngestRequest $request,
        DocumentIngestor $ingestor,
        DocumentChunker $chunker,
    ): JsonResponse {
        $data = $request->validated();
        $metadata = $data['metadata'] ?? [];

        if (config('rag.queue_ingestion', false)) {
            IngestDocumentJob::dispatch($data['source'], $data['content'], $metadata);

            return response()->json([
                'message' => 'Document queued for ingestion.',
                'source' => $data['source'],
                'chunks' => count($chunker->chunk($data['content'])),
            ], 202);
        }

        $documents = $ingestor->ingest($data['source'], $data['content'], $metadata);

        return response()->json([
            'message' => 'Document ingested.',
            'source' => $data['source'],
            'chunks' => $documents->count(),
        ], 201);
    }

    /**
     * Answer a question grounded in the most relevant ingested chunks.
     */
    public function ask(AskRequest $request, RagPipeline $pipeline): JsonResponse
    {
        $data = $request->validated();

        return response()->json(
            $pipeline->ask($data['question'], $data['top_k'] ?? null),
        );
    }
}
