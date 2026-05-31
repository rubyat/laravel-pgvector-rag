# rubyat/laravel-rag

Add semantic search and Retrieval-Augmented Generation (RAG) to a Laravel app with PostgreSQL + pgvector.

## Install

This package is developed as a local path package of the parent application. To use it elsewhere, add it to your `composer.json` and publish the config:

```bash
php artisan vendor:publish --tag=rag-config
php artisan migrate
```

## What it provides

- `RagStarter\Ingestion\DocumentChunker` — character-bounded, overlapping chunking.
- `RagStarter\Ingestion\DocumentIngestor` — chunk → embed → store as `Document` rows.
- `RagStarter\Ingestion\IngestDocumentJob` — queueable ingestion.
- `RagStarter\Retrieval\VectorRetriever` — cosine top-k search via pgvector (HNSW).
- `RagStarter\Rag\RagPipeline` — retrieve → ground → answer, with citations.
- `RagStarter\Contracts\EmbeddingDriver` / `ChatDriver` — swap providers.
  - Drivers: `OpenAi*` (HTTP) and deterministic `Fake*` for tests/offline.
- HTTP routes: `POST {prefix}/ingest`, `POST {prefix}/ask` (default prefix `api/rag`).

## Configuration

See `config/rag.php`. Drivers, dimensions, chunking, `top_k`, route prefix/middleware and
sync-vs-queued ingestion are all configurable via env. The `fake` drivers require no network.

## Models

Documents are stored one row per chunk in the `documents` table (`source`, `chunk_index`,
`content`, `metadata`, `embedding vector(n)`), indexed with HNSW (`vector_cosine_ops`).
