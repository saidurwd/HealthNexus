<?php

namespace App\Models\Ipd;

use App\Models\Department;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\Specialty;
use App\Models\User;
use App\Models\WorkflowInstance;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class IpdAdmissionRequest extends Model
{
    use HasFactory;

    public const STATUS_REQUESTED = 'requested';
    public const STATUS_PENDING_APPROVAL = 'pending_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'company_id', 'branch_id', 'patient_id', 'source_encounter_id', 'requesting_provider_id',
        'department_id', 'specialty_id', 'admission_type_id', 'admission_source_id',
        'reason', 'provisional_diagnosis', 'priority', 'expected_length_of_stay_days',
        'expected_admission_date', 'expected_discharge_date', 'required_bed_type_id',
        'isolation_requirement', 'special_requirements', 'notes', 'status',
        'workflow_instance_id', 'requested_by', 'approved_by', 'approved_at',
        'rejected_at', 'rejection_reason', 'cancelled_at', 'cancelled_by', 'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'expected_admission_date' => 'date',
            'expected_discharge_date' => 'date',
            'expected_length_of_stay_days' => 'integer',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function sourceEncounter(): BelongsTo { return $this->belongsTo(Encounter::class, 'source_encounter_id'); }
    public function requestingProvider(): BelongsTo { return $this->belongsTo(Provider::class, 'requesting_provider_id'); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function specialty(): BelongsTo { return $this->belongsTo(Specialty::class); }
    public function admissionType(): BelongsTo { return $this->belongsTo(IpdAdmissionType::class, 'admission_type_id'); }
    public function admissionSource(): BelongsTo { return $this->belongsTo(IpdAdmissionSource::class, 'admission_source_id'); }
    public function requiredBedType(): BelongsTo { return $this->belongsTo(IpdBedType::class, 'required_bed_type_id'); }
    public function workflowInstance(): BelongsTo { return $this->belongsTo(WorkflowInstance::class); }
    public function requestedBy(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function approvedBy(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function cancelledBy(): BelongsTo { return $this->belongsTo(User::class, 'cancelled_by'); }
    public function admission(): HasOne { return $this->hasOne(IpdAdmission::class, 'admission_request_id'); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
