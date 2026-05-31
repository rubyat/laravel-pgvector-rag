<?php

namespace RagStarter\Ingestion;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Ingests a document in the background so large bodies of text do not block
 * the request. Runs inline when QUEUE_CONNECTION is "sync".
 */
class IngestDocumentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $source,
        public string $content,
        public array $metadata = [],
    ) {}

    public function handle(DocumentIngestor $ingestor): void
    {
        $ingestor->ingest($this->source, $this->content, $this->metadata);
    }
}
