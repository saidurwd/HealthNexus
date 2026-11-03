<?php

namespace App\Models\Blood;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BloodTransfusion extends Model
{
    use HasFactory, HasPublicId;

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['started_at' => 'datetime', 'ended_at' => 'datetime', 'verification_override' => 'boolean'];

    /** @return BelongsTo<BloodIssue, $this> */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(BloodIssue::class, 'issue_id');
    }

    /** @return HasMany<BloodTransfusionReaction, $this> */
    public function reactions(): HasMany
    {
        return $this->hasMany(BloodTransfusionReaction::class, 'transfusion_id');
    }
}
