<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'country_id',
        'state_id',
        'enterprise_patient_no',
        'national_identifier',
        'first_name',
        'middle_name',
        'last_name',
        'date_of_birth',
        'sex',
        'blood_group',
        'phone',
        'email',
        'address',
        'city',
        'postal_code',
        'emergency_contact',
        'notes',
        'status',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'emergency_contact' => 'array',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function branchRegistrations(): HasMany
    {
        return $this->hasMany(PatientBranchRegistration::class);
    }

    public function encounters(): HasMany
    {
        return $this->hasMany(Encounter::class);
    }

    public function identifiers(): HasMany
    {
        return $this->hasMany(PatientIdentifier::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(PatientContact::class);
    }

    public function allergies(): HasMany
    {
        return $this->hasMany(PatientAllergy::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(PatientAlert::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(PatientHistory::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PatientDocument::class);
    }

    public function emergencyContacts(): HasMany
    {
        return $this->contacts()->where('is_emergency', true);
    }

    public function nextOfKin(): HasMany
    {
        return $this->contacts()->where('is_emergency', false);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function vitalSigns(): HasMany
    {
        return $this->hasMany(VitalSign::class);
    }

    public function diagnoses(): HasMany
    {
        return $this->hasMany(Diagnosis::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function investigationOrders(): HasMany
    {
        return $this->hasMany(InvestigationOrder::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->middle_name.' '.$this->last_name);
    }

    public function activeAlerts(): HasMany
    {
        return $this->alerts()->where('status', 'active');
    }
}
