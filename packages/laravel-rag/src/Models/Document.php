<?php

namespace Rubyat\LaravelRag\Models;

use Illuminate\Database\Eloquent\Model;
use Rubyat\LaravelRag\Casts\VectorCast;

/**
 * A single embedded chunk of a source document.
 *
 * @property int $id
 * @property string $source
 * @property int $chunk_index
 * @property string $content
 * @property array<string, mixed>|null $metadata
 * @property array<int, float>|null $embedding
 */
class Document extends Model
{
    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'embedding' => VectorCast::class,
        'chunk_index' => 'integer',
    ];

    public function getTable(): string
    {
        return config('rag.table', 'documents');
    }
}
