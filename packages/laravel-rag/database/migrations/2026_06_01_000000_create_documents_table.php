<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $table = config('rag.table', 'documents');
        $dimensions = (int) config('rag.dimensions', 1536);
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
        }

        Schema::create($table, function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('source')->index();
            $blueprint->unsignedInteger('chunk_index')->default(0);
            $blueprint->text('content');
            $blueprint->json('metadata')->nullable();
            $blueprint->timestamps();
        });

        if ($driver === 'pgsql') {
            // pgvector column + approximate-nearest-neighbour index (cosine).
            DB::statement("ALTER TABLE {$table} ADD COLUMN embedding vector({$dimensions})");
            DB::statement("CREATE INDEX {$table}_embedding_ivfflat_idx ON {$table} USING ivfflat (embedding vector_cosine_ops) WITH (lists = 100)");
        } else {
            // Fallback for non-pgvector drivers: store the raw vector as text so
            // the schema still migrates. Similarity search requires PostgreSQL.
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->text('embedding')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('rag.table', 'documents'));
    }
};
