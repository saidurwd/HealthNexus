<?php

namespace App\Models\Icu;

use App\Models\Branch;
use App\Models\Concerns\HasPublicId;
use App\Models\Diagnosis;
use App\Models\Encounter;
use App\Models\Ipd\IpdAdmission;
use App\Models\Ipd\IpdBed;
use App\Models\Ipd\IpdWard;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class IcuEpisode extends Model
{
    use HasFactory, HasPublicId;

    public const STATUS_ADMITTED = 'admitted';

    public const STATUS_STEP_DOWN = 'step_down';

    public const STATUS_TRANSFERRED = 'transferred';

    public const STATUS_DISCHARGED = 'discharged';

    protected $fillable = [
        'organization_id', 'branch_id', 'episode_number', 'patient_id', 'encounter_id', 'admission_id',
        'admission_request_id', 'icu_unit_id', 'icu_bed_id', 'admission_reason', 'primary_diagnosis_id',
        'admitting_provider_id', 'attending_provider_id', 'priority', 'status', 'blood_requirement',
        'admitted_at', 'discharged_at', 'created_by', 'completed_by', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'admitted_at' => 'datetime',
            'discharged_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(IpdAdmission::class, 'admission_id');
    }

    public function admissionRequest(): BelongsTo
    {
        return $this->belongsTo(IcuAdmissionRequest::class, 'admission_request_id');
    }

    public function icuUnit(): BelongsTo
    {
        return $this->belongsTo(IpdWard::class, 'icu_unit_id');
    }

    public function icuBed(): BelongsTo
    {
        return $this->belongsTo(IpdBed::class, 'icu_bed_id');
    }

    public function primaryDiagnosis(): BelongsTo
    {
        return $this->belongsTo(Diagnosis::class, 'primary_diagnosis_id');
    }

    public function admittingProvider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'admitting_provider_id');
    }

    public function attendingProvider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'attending_provider_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(IcuEpisodeEvent::class, 'episode_id');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(IcuAssessment::class, 'episode_id');
    }

    public function observations(): HasMany
    {
        return $this->hasMany(IcuObservation::class, 'episode_id');
    }

    public function progressNotes(): HasMany
    {
        return $this->hasMany(IcuProgressNote::class, 'episode_id');
    }

    public function handoverRecords(): HasMany
    {
        return $this->hasMany(IcuHandoverRecord::class, 'episode_id');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(IcuDevice::class, 'episode_id');
    }

    public function infusions(): HasMany
    {
        return $this->hasMany(IcuInfusion::class, 'episode_id');
    }

    public function respiratorySupport(): HasMany
    {
        return $this->hasMany(IcuRespiratorySupport::class, 'episode_id');
    }

    public function ventilatorEvents(): HasMany
    {
        return $this->hasMany(IcuVentilatorEvent::class, 'episode_id');
    }

    public function fluidBalanceEntries(): HasMany
    {
        return $this->hasMany(IcuFluidBalanceEntry::class, 'episode_id');
    }

    public function nutritionRecords(): HasMany
    {
        return $this->hasMany(IcuNutritionRecord::class, 'episode_id');
    }

    public function scoreResults(): HasMany
    {
        return $this->hasMany(IcuClinicalScoreResult::class, 'episode_id');
    }

    public function criticalAlerts(): HasMany
    {
        return $this->hasMany(IcuCriticalAlert::class, 'episode_id');
    }

    public function criticalEvents(): HasMany
    {
        return $this->hasMany(IcuCriticalEvent::class, 'episode_id');
    }

    public function procedureRecords(): HasMany
    {
        return $this->hasMany(IcuProcedureRecord::class, 'episode_id');
    }

    public function familyCommunications(): HasMany
    {
        return $this->hasMany(IcuFamilyCommunication::class, 'episode_id');
    }

    public function dischargeSummary(): HasOne
    {
        return $this->hasOne(IcuDischargeSummary::class, 'episode_id');
    }

    public function chargeEvents(): HasMany
    {
        return $this->hasMany(IcuChargeEvent::class, 'episode_id');
    }

    public function scopeForTenant($query, int $organizationId, ?int $branchId = null)
    {
        return $query->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
