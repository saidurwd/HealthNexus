<?php

namespace App\Models\Blood;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BloodUnitEvent extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = ['occurred_at' => 'datetime'];

    protected static function booted(): void
    {
        // The trail is append-only: nothing edits or deletes it (traceability).
        static::updating(fn () => throw new \LogicException('Blood unit events are append-only.'));
        static::deleting(fn () => throw new \LogicException('Blood unit events are append-only.'));
    }
}
