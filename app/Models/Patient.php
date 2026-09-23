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
        'patient_type_id',
        'gender_id',
        'marital_status_id',
        'nationality_id',
        'enterprise_patient_no',
        'national_identifier',
        'first_name',
        'middle_name',
        'last_name',
        'preferred_name',
        'display_name',
        'date_of_birth',
        'dob_unknown',
        'estimated_age',
        'estimated_age_unit',
        'sex',
        'blood_group',
        'rh_factor',
        'phone',
        'email',
        'address',
        'city',
        'postal_code',
        'emergency_contact',
        'notes',
        'status',
        'merged_into_patient_id',
        'deceased_at',
        'is_temporary',
        'is_unknown',
        'registered_at',
        'registered_by',
        'photo_file_id',
        'portal_enabled',
        'portal_user_id',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'emergency_contact' => 'array',
        'is_active' => 'boolean',
        'dob_unknown' => 'boolean',
        'estimated_age' => 'integer',
        'deceased_at' => 'datetime',
        'is_temporary' => 'boolean',
        'is_unknown' => 'boolean',
        'registered_at' => 'datetime',
        'portal_enabled' => 'boolean',
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

    public function addresses(): HasMany
    {
        return $this->hasMany(PatientAddress::class);
    }

    public function guardians(): HasMany
    {
        return $this->hasMany(PatientGuardian::class);
    }

    public function consents(): HasMany
    {
        return $this->hasMany(PatientConsent::class);
    }

    public function activeConsents(): HasMany
    {
        return $this->consents()->where('status', 'active');
    }

    public function preference(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PatientPreference::class);
    }

    public function amendments(): HasMany
    {
        return $this->hasMany(PatientAmendment::class);
    }

    public function timelineEvents(): HasMany
    {
        return $this->hasMany(PatientTimelineEvent::class);
    }

    public function portalAccount(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PatientPortalAccount::class);
    }

    public function patientType(): BelongsTo
    {
        return $this->belongsTo(PatientType::class);
    }

    public function gender(): BelongsTo
    {
        return $this->belongsTo(Gender::class);
    }

    public function maritalStatus(): BelongsTo
    {
        return $this->belongsTo(MaritalStatus::class);
    }

    public function nationality(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'nationality_id');
    }

    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'merged_into_patient_id');
    }

    public function mergedFrom(): HasMany
    {
        return $this->hasMany(Patient::class, 'merged_into_patient_id');
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(File::class, 'photo_file_id');
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
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

    /**
     * The name shown in UI/print contexts: an explicit display_name (set when a locale needs a
     * non-Western ordering) wins, then preferred_name, falling back to the full legal name.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->attributes['display_name'] ?? $this->preferred_name ?? $this->full_name;
    }

    public function activeAlerts(): HasMany
    {
        return $this->alerts()->where('status', 'active');
    }
}
