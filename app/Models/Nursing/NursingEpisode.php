<?php

namespace App\Models\Nursing;

use App\Models\Encounter;
use App\Models\Ipd\IpdAdmission;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NursingEpisode extends Model
{
    public const STATUS_PLANNED = 'planned';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_TRANSFERRED = 'transferred';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'company_id', 'branch_id', 'admission_id', 'patient_id', 'encounter_id',
        'primary_nurse_id', 'status', 'start_at', 'end_at', 'created_by',
    ];

    protected function casts(): array
    {
        return ['start_at' => 'datetime', 'end_at' => 'datetime'];
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(IpdAdmission::class, 'admission_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function primaryNurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'primary_nurse_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(NursingAssignment::class, 'episode_id');
    }

    /**
     * The sole source of "who is currently assigned" — never denormalized onto the episode row.
     */
    public function currentAssignments(): HasMany
    {
        return $this->assignments()->whereNull('ended_at');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(NursingAssessment::class, 'episode_id');
    }

    public function observations(): HasMany
    {
        return $this->hasMany(NursingObservation::class, 'episode_id');
    }

    public function painAssessments(): HasMany
    {
        return $this->hasMany(NursingPainAssessment::class, 'episode_id');
    }

    public function riskAssessments(): HasMany
    {
        return $this->hasMany(NursingRiskAssessment::class, 'episode_id');
    }

    public function intakeOutputRecords(): HasMany
    {
        return $this->hasMany(NursingIntakeOutputRecord::class, 'episode_id');
    }

    public function carePlans(): HasMany
    {
        return $this->hasMany(NursingCarePlan::class, 'episode_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(NursingTask::class, 'episode_id');
    }

    public function medicationAdministrations(): HasMany
    {
        return $this->hasMany(NursingMedicationAdministration::class, 'episode_id');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(NursingDevice::class, 'episode_id');
    }

    public function ivInfusions(): HasMany
    {
        return $this->hasMany(NursingIvInfusion::class, 'episode_id');
    }

    public function woundAssessments(): HasMany
    {
        return $this->hasMany(NursingWoundAssessment::class, 'episode_id');
    }

    public function education(): HasMany
    {
        return $this->hasMany(NursingEducation::class, 'episode_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(NursingNote::class, 'episode_id');
    }

    public function handovers(): HasMany
    {
        return $this->hasMany(NursingHandover::class, 'episode_id');
    }

    public function escalations(): HasMany
    {
        return $this->hasMany(NursingEscalation::class, 'episode_id');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(NursingAlert::class, 'episode_id');
    }

    public function dischargeChecklist(): HasOne
    {
        return $this->hasOne(NursingDischargeChecklist::class, 'episode_id');
    }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
