<?php

namespace App\Models\Ot;

use App\Models\Branch;
use App\Models\Concerns\HasPublicId;
use App\Models\Encounter;
use App\Models\Ipd\IpdAdmission;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\PatientConsent;
use App\Models\Provider;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OtSurgery extends Model
{
    use HasPublicId;

    public const STATUS_REQUESTED = 'requested';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_PREOPERATIVE_PREPARATION = 'preoperative_preparation';

    public const STATUS_READY_FOR_OT = 'ready_for_ot';

    public const STATUS_PATIENT_RECEIVED = 'patient_received';

    public const STATUS_IN_PROCEDURE = 'in_procedure';

    public const STATUS_PROCEDURE_COMPLETED = 'procedure_completed';

    public const STATUS_RECOVERY = 'recovery';

    public const STATUS_TRANSFERRED = 'transferred';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_POSTPONED = 'postponed';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_NO_SHOW = 'no_show';

    public const STATUS_ABORTED = 'aborted';

    protected $fillable = [
        'organization_id', 'branch_id', 'surgery_number', 'patient_id', 'admission_id', 'encounter_id',
        'surgery_request_id', 'primary_procedure_id', 'specialty_id', 'priority', 'surgery_type',
        'site', 'laterality', 'theatre_id', 'scheduled_date', 'scheduled_start_at', 'scheduled_end_at', 'actual_start_at',
        'actual_end_at', 'status', 'primary_surgeon_id', 'assistant_surgeon_id', 'anesthetist_id',
        'consent_id', 'cancellation_reason', 'created_by', 'received_at', 'completed_by', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'scheduled_start_at' => 'datetime',
            'scheduled_end_at' => 'datetime',
            'actual_start_at' => 'datetime',
            'actual_end_at' => 'datetime',
            'received_at' => 'datetime',
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

    public function admission(): BelongsTo
    {
        return $this->belongsTo(IpdAdmission::class, 'admission_id');
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function surgeryRequest(): BelongsTo
    {
        return $this->belongsTo(OtSurgeryRequest::class, 'surgery_request_id');
    }

    public function primaryProcedure(): BelongsTo
    {
        return $this->belongsTo(OtProcedure::class, 'primary_procedure_id');
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    public function theatre(): BelongsTo
    {
        return $this->belongsTo(OtTheatre::class, 'theatre_id');
    }

    public function primarySurgeon(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'primary_surgeon_id');
    }

    public function assistantSurgeon(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'assistant_surgeon_id');
    }

    public function anesthetist(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'anesthetist_id');
    }

    public function consent(): BelongsTo
    {
        return $this->belongsTo(PatientConsent::class, 'consent_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function procedures(): HasMany
    {
        return $this->hasMany(OtSurgeryProcedure::class, 'surgery_id');
    }

    public function scheduleEvents(): HasMany
    {
        return $this->hasMany(OtSurgeryScheduleEvent::class, 'surgery_id');
    }

    public function team(): HasMany
    {
        return $this->hasMany(OtSurgicalTeam::class, 'surgery_id');
    }

    public function currentTeam(): HasMany
    {
        return $this->team()->whereNull('removed_at');
    }

    public function safetyChecklists(): HasMany
    {
        return $this->hasMany(OtSurgicalSafetyChecklist::class, 'surgery_id');
    }

    public function preoperativeAssessment(): HasOne
    {
        return $this->hasOne(OtPreoperativeAssessment::class, 'surgery_id');
    }

    public function preoperativeChecklist(): HasOne
    {
        return $this->hasOne(OtPreoperativeChecklist::class, 'surgery_id');
    }

    public function operativeNote(): HasOne
    {
        return $this->hasOne(OtOperativeNote::class, 'surgery_id');
    }

    public function complications(): HasMany
    {
        return $this->hasMany(OtSurgicalComplication::class, 'surgery_id');
    }

    public function counts(): HasMany
    {
        return $this->hasMany(OtSurgicalCount::class, 'surgery_id');
    }

    public function specimens(): HasMany
    {
        return $this->hasMany(OtSpecimen::class, 'surgery_id');
    }

    public function implants(): HasMany
    {
        return $this->hasMany(OtImplant::class, 'surgery_id');
    }

    public function recoveryRecord(): HasOne
    {
        return $this->hasOne(OtRecoveryRecord::class, 'surgery_id');
    }

    public function scopeForTenant($query, int $organizationId, ?int $branchId = null)
    {
        return $query->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
