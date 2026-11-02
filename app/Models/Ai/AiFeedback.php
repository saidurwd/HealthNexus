<?php

namespace App\Models\Ai;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiFeedback extends Model
{
    protected $table = 'ai_feedback';

    protected $guarded = ['id'];

    public const UPDATED_AT = null;

    /** @return BelongsTo<AiOutput, $this> */
    public function output(): BelongsTo
    {
        return $this->belongsTo(AiOutput::class, 'output_id');
    }
}
