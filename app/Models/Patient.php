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

    public function identifiers(): HasMany
    {
        return $this->hasMany(PatientIdentifier::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(PatientContact::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->middle_name.' '.$this->last_name);
    }
}
