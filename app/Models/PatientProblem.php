<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientProblem extends Model
{
    protected $fillable = [
        'patient_id',
        'organization_id',
        'problem_code',
        'problem_name',
        'coding_system',
        'status',
        'onset_date',
        'resolved_date',
        'source_encounter_id',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'onset_date' => 'date',
        'resolved_date' => 'date',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function sourceEncounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class, 'source_encounter_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
