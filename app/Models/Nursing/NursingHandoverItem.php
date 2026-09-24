<?php

namespace App\Models\Nursing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NursingHandoverItem extends Model
{
    protected $fillable = ['handover_id', 'section', 'content', 'source_type', 'source_id'];

    public function handover(): BelongsTo
    {
        return $this->belongsTo(NursingHandover::class, 'handover_id');
    }
}
