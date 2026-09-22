<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Encounter extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'patient_id',
        'appointment_id',
        'encounter_no',
        'encounter_type',
        'encounter_type_id',
        'provider_id',
        'department_id',
        'specialty_id',
        'encounter_date',
        'started_at',
        'ended_at',
        'status',
        'priority',
        'source',
        'chief_complaint_summary',
        'reason_for_visit',
        'referred_by',
        'notes',
        'created_by',
        'completed_by',
        'completed_at',
        'locked_at',
        'locked_by',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'encounter_date' => 'date',
        'completed_at' => 'datetime',
        'locked_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function encounterType(): BelongsTo
    {
        return $this->belongsTo(EncounterType::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(EncounterStatusHistory::class);
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(EncounterComplaint::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(EncounterHistory::class);
    }

    public function examinations(): HasMany
    {
        return $this->hasMany(EncounterExamination::class);
    }

    public function reviewOfSystems(): HasMany
    {
        return $this->hasMany(EncounterReviewOfSystem::class);
    }

    public function vitals(): HasMany
    {
        return $this->hasMany(VitalSign::class);
    }

    public function diagnoses(): HasMany
    {
        return $this->hasMany(Diagnosis::class);
    }

    public function problems(): HasMany
    {
        return $this->hasMany(PatientProblem::class);
    }

    public function procedures(): HasMany
    {
        return $this->hasMany(EncounterProcedure::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(ClinicalOrder::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(EncounterReferral::class);
    }

    public function instructions(): HasMany
    {
        return $this->hasMany(EncounterInstruction::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(EncounterNote::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EncounterDocument::class);
    }

    public function amendments(): HasMany
    {
        return $this->hasMany(EncounterAmendment::class);
    }
}
