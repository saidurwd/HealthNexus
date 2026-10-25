<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Public ULID for records that identify a person or a clinical event (guideline 9.1, H-12).
 * Routes and API payloads use public_id; the numeric id never leaves the server.
 */
trait HasPublicId
{
    public static function bootHasPublicId(): void
    {
        static::creating(function ($model) {
            $model->public_id ??= (string) Str::ulid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
