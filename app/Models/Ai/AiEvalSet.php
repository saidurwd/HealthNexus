<?php

namespace App\Models\Ai;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiEvalSet extends Model
{
    protected $table = 'ai_eval_sets';

    protected $guarded = ['id'];

    /** @return HasMany<AiEvalCase, $this> */
    public function cases(): HasMany
    {
        return $this->hasMany(AiEvalCase::class, 'set_id');
    }
}
