<?php

namespace App\Models\Ai;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiEvalRun extends Model
{
    protected $table = 'ai_eval_runs';

    protected $guarded = ['id'];

    public const UPDATED_AT = null;

    protected $casts = ['passed' => 'boolean', 'languages' => 'array', 'thresholds' => 'array', 'recall' => 'decimal:4'];

    /** @return BelongsTo<AiModel, $this> */
    public function model(): BelongsTo
    {
        return $this->belongsTo(AiModel::class, 'model_id');
    }

    /** @return BelongsTo<AiEvalSet, $this> */
    public function set(): BelongsTo
    {
        return $this->belongsTo(AiEvalSet::class, 'set_id');
    }
}
