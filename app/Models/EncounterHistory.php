<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EncounterHistory extends Model
{
    protected $fillable = [
        'encounter_id',
        'patient_id',
        'company_id',
        'history_type',
        'onset',
        'duration',
        'course',
        'severity',
        'associated_symptoms',
        'aggravating_factors',
        'relieving_factors',
        'clinical_notes',
    ];

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
