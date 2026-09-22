<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EncounterTemplateSection extends Model
{
    protected $fillable = [
        'encounter_template_id',
        'section_name',
        'section_key',
        'sort_order',
        'is_required',
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(EncounterTemplate::class, 'encounter_template_id');
    }
}
