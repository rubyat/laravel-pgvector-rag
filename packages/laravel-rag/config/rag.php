<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Drivers
    |--------------------------------------------------------------------------
    |
    | Which embedding and chat drivers to use. The "fake" drivers are
    | deterministic and require no network access, which makes them ideal
    | for local experimentation and the test suite.
    |
    | Supported embedding drivers: "openai", "fake"
    | Supported chat drivers:      "openai", "fake"
    |
    */

    'embedding_driver' => env('RAG_EMBEDDING_DRIVER', 'openai'),

    'chat_driver' => env('RAG_CHAT_DRIVER', 'openai'),

    /*
    |--------------------------------------------------------------------------
    | OpenAI
    |--------------------------------------------------------------------------
    */

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_uri' => env('OPENAI_BASE_URI', 'https://api.openai.com/v1'),
        'embedding_model' => env('RAG_EMBEDDING_MODEL', 'text-embedding-3-small'),
        'chat_model' => env('RAG_CHAT_MODEL', 'gpt-4o-mini'),
        'timeout' => (int) env('RAG_OPENAI_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Embedding Dimensions
    |--------------------------------------------------------------------------
    |
    | The vector size stored in the documents table. This MUST match the
    | dimension produced by the configured embedding model. Changing it
    | requires a new migration because the pgvector column is fixed-length.
    | text-embedding-3-small => 1536.
    |
    */

    'dimensions' => (int) env('RAG_DIMENSIONS', 1536),

    /*
    |--------------------------------------------------------------------------
    | Chunking
    |--------------------------------------------------------------------------
    |
    | How source documents are split before embedding. Sizes are measured in
    | characters. Overlap keeps context continuous across chunk boundaries.
    |
    */

    'chunk_size' => (int) env('RAG_CHUNK_SIZE', 1000),

    'chunk_overlap' => (int) env('RAG_CHUNK_OVERLAP', 200),

    /*
    |--------------------------------------------------------------------------
    | Queue Ingestion
    |--------------------------------------------------------------------------
    |
    | When true, the ingest endpoint dispatches IngestDocumentJob to your queue
    | (run a worker to process it). When false, documents are ingested inline
    | during the request, which is simplest for demos and small documents.
    |
    */

    'queue_ingestion' => (bool) env('RAG_QUEUE_INGESTION', false),

    /*
    |--------------------------------------------------------------------------
    | Retrieval
    |--------------------------------------------------------------------------
    |
    | How many of the nearest chunks to retrieve for a query.
    |
    */

    'top_k' => (int) env('RAG_TOP_K', 4),

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | The package registers POST {prefix}/ingest and POST {prefix}/ask. Set
    | "register_routes" to false to wire your own routes against the services.
    |
    */

    'register_routes' => env('RAG_REGISTER_ROUTES', true),

    'route_prefix' => env('RAG_ROUTE_PREFIX', 'api/rag'),

    'middleware' => ['api'],

    /*
    |--------------------------------------------------------------------------
    | Database
    |--------------------------------------------------------------------------
    |
    | The table used to store chunks and their embeddings.
    |
    */

    'table' => env('RAG_TABLE', 'documents'),

];
