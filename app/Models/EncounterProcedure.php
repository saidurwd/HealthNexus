<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EncounterProcedure extends Model
{
    protected $fillable = [
        'encounter_id',
        'patient_id',
        'company_id',
        'procedure_code',
        'procedure_name',
        'procedure_date',
        'provider_id',
        'notes',
        'status',
        'recorded_by',
        'recorded_at',
    ];

    protected $casts = [
        'procedure_date' => 'date',
        'recorded_at' => 'datetime',
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

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
