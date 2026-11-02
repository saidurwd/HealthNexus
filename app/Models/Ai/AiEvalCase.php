<?php

namespace App\Models\Ai;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiEvalCase extends Model
{
    protected $table = 'ai_eval_cases';

    protected $guarded = ['id'];

    protected $casts = ['items' => 'array', 'expected' => 'array', 'forbidden' => 'array'];

    /** @return BelongsTo<AiEvalSet, $this> */
    public function set(): BelongsTo
    {
        return $this->belongsTo(AiEvalSet::class, 'set_id');
    }
}
