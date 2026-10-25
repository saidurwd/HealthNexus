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
            $model->setAttribute('public_id', $model->getAttribute('public_id') ?? (string) Str::ulid());
        });
    }

    /** The numeric id never leaves the server in JSON: callers identify the record by `public_id` (guideline 9.1). */
    public function initializeHasPublicId(): void
    {
        $this->makeHidden('id');
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** A row written by raw SQL (an import, a script) has no public id yet; give it one the first time it is linked to. */
    public function getRouteKey(): mixed
    {
        if ($this->exists && ($this->attributes['public_id'] ?? null) === null) {
            $this->forceFill(['public_id' => (string) Str::ulid()])->saveQuietly();
        }

        return $this->getAttribute('public_id');
    }
}
