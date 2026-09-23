<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientGuardian extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'patient_id',
        'guardian_patient_id',
        'name',
        'relationship',
        'phone',
        'email',
        'national_identifier',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function guardianPatient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'guardian_patient_id');
    }
}
