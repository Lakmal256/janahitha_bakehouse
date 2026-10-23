<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Gives every row a stable string key that the browser app uses as its document id.
 */
trait HasSourceKey
{
    public static function bootHasSourceKey(): void
    {
        static::creating(function (self $model): void {
            $model->source_key ??= Str::lower((string) Str::ulid());
        });
    }
}
