<?php

namespace RagStarter\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use RagStarter\Http\Requests\IngestRequest;
use RagStarter\Ingestion\DocumentChunker;
use RagStarter\Ingestion\IngestDocumentJob;

class RagController extends Controller
{
    /**
     * Chunk, embed and store a source document.
     */
    public function ingest(IngestRequest $request, DocumentChunker $chunker): JsonResponse
    {
        $data = $request->validated();
        $metadata = $data['metadata'] ?? [];

        $chunkCount = count($chunker->chunk($data['content']));

        IngestDocumentJob::dispatch($data['source'], $data['content'], $metadata);

        return response()->json([
            'message' => 'Document queued for ingestion.',
            'source' => $data['source'],
            'chunks' => $chunkCount,
        ], 201);
    }
}
