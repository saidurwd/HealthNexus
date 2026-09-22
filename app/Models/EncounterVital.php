<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EncounterVital extends Model
{
    use HasFactory;

    protected $fillable = [
        'encounter_id',
        'patient_id',
        'recorded_by',
        'recorded_at',
        'temperature',
        'temperature_unit',
        'systolic',
        'diastolic',
        'bp_unit',
        'pulse_rate',
        'respiratory_rate',
        'height',
        'weight',
        'bmi',
        'oxygen_saturation',
        'notes',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'temperature' => 'decimal:2',
        'systolic' => 'integer',
        'diastolic' => 'integer',
        'pulse_rate' => 'integer',
        'respiratory_rate' => 'integer',
        'height' => 'decimal:2',
        'weight' => 'decimal:2',
        'bmi' => 'decimal:2',
        'oxygen_saturation' => 'integer',
    ];

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
