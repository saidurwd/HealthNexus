<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EncounterComplaint extends Model
{
    protected $fillable = [
        'encounter_id',
        'patient_id',
        'company_id',
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

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
