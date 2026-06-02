<?php

namespace Rubyat\LaravelRag\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Converts between a PHP array of floats and the pgvector text literal
 * (e.g. [0.12,0.34,...]). pgvector returns the column as such a literal and
 * implicitly casts the same literal back to a vector on write.
 *
 * @implements CastsAttributes<array<int, float>|null, array<int, float>|null>
 */
class VectorCast implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return array<int, float>|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? array_map('floatval', $decoded) : null;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        return '['.implode(',', array_map(static fn ($v) => (float) $v, $value)).']';
    }
}
