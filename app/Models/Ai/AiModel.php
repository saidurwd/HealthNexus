<?php

namespace App\Models\Ai;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiModel extends Model
{
    protected $table = 'ai_models';

    protected $guarded = ['id'];

    protected $casts = ['approved_at' => 'datetime'];

    /** @return BelongsTo<AiProvider, $this> */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'provider_id');
    }

    /** @return HasMany<AiEvalRun, $this> */
    public function runs(): HasMany
    {
        return $this->hasMany(AiEvalRun::class, 'model_id');
    }
}
