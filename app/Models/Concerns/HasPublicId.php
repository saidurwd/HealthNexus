<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Public ULID for records that identify a person or a clinical event (guideline 9.1, H-12).
 * Routes and API payloads use public_id; the numeric id never leaves the server.
 *
 * @property string $public_id
 */
trait HasPublicId
{
    public static function bootHasPublicId(): void
    {
        static::creating(function ($model) {
            $model->setAttribute('public_id', $model->getAttribute('public_id') ?? (string) Str::ulid());
        });
    }

    /**
     * Also assigned at insert time, not only by the `creating` event: seeders run with model events switched off (WithoutModelEvents),
     * and a record without a public_id must never reach the database.
     */
    protected function performInsert(Builder $query)
    {
        if (! $this->getAttribute('public_id')) {
            $this->setAttribute('public_id', (string) Str::ulid());
        }

        return parent::performInsert($query);
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
