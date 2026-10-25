<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EncounterComplaint extends Model
{
    protected $fillable = [
        'encounter_id',
        'patient_id',
        'organization_id',
        'complaint',
        'duration',
        'duration_unit',
        'onset',
        'severity',
        'location',
        'notes',
        'sort_order',
    ];

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
