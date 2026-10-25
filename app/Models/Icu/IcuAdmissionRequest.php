<?php

namespace App\Models\Icu;

use App\Models\Diagnosis;
use App\Models\Encounter;
use App\Models\Ipd\IpdAdmission;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use App\Models\WorkflowInstance;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class IcuAdmissionRequest extends Model
{
    use HasFactory;

    public const STATUS_REQUESTED = 'requested';

    public const STATUS_PENDING_APPROVAL = 'pending_approval';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'company_id', 'branch_id', 'patient_id', 'encounter_id', 'admission_id', 'requesting_provider_id',
        'admission_reason', 'primary_diagnosis_id', 'priority', 'is_emergency', 'override_reason',
        'status', 'workflow_instance_id', 'requested_by', 'accepted_by', 'accepted_at',
        'rejected_at', 'rejection_reason', 'cancelled_at', 'cancelled_by', 'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'is_emergency' => 'boolean',
            'accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function encounter(): BelongsTo { return $this->belongsTo(Encounter::class); }
    public function admission(): BelongsTo { return $this->belongsTo(IpdAdmission::class, 'admission_id'); }
    public function requestingProvider(): BelongsTo { return $this->belongsTo(Provider::class, 'requesting_provider_id'); }
    public function primaryDiagnosis(): BelongsTo { return $this->belongsTo(Diagnosis::class, 'primary_diagnosis_id'); }
    public function workflowInstance(): BelongsTo { return $this->belongsTo(WorkflowInstance::class, 'workflow_instance_id'); }
    public function requestedBy(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function acceptedBy(): BelongsTo { return $this->belongsTo(User::class, 'accepted_by'); }
    public function cancelledBy(): BelongsTo { return $this->belongsTo(User::class, 'cancelled_by'); }
    public function episode(): HasOne { return $this->hasOne(IcuEpisode::class, 'admission_request_id'); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
