<?php

namespace App\Models\Insurance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceClaimEvent extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = ['occurred_at' => 'datetime', 'amount' => 'decimal:2'];

    protected static function booted(): void
    {
        // The timeline is append-only (a claim's history is evidence).
        static::updating(fn () => throw new \LogicException('Claim events are append-only.'));
        static::deleting(fn () => throw new \LogicException('Claim events are append-only.'));
    }
}
