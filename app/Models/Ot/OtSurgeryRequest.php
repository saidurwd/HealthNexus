<?php

namespace App\Models\Ot;

use App\Models\Concerns\HasPublicId;
use App\Models\Diagnosis;
use App\Models\Encounter;
use App\Models\Ipd\IpdAdmission;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\Specialty;
use App\Models\User;
use App\Models\WorkflowInstance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OtSurgeryRequest extends Model
{
    use HasPublicId;

    public const STATUS_REQUESTED = 'requested';

    public const STATUS_PENDING_APPROVAL = 'pending_approval';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'organization_id', 'branch_id', 'patient_id', 'encounter_id', 'admission_id', 'clinical_order_id',
        'requesting_provider_id', 'specialty_id', 'procedure_id', 'indication', 'site', 'laterality', 'diagnosis_id',
        'priority', 'surgery_type', 'preferred_date', 'estimated_duration_minutes',
        'required_theatre_capability', 'anesthesia_requirement', 'special_equipment',
        'implant_requirement', 'blood_requirement', 'specimen_requirement', 'notes', 'status',
        'workflow_instance_id', 'requested_by', 'requested_at', 'approved_by', 'approved_at',
        'rejection_reason', 'rejected_at', 'cancelled_by', 'cancellation_reason', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'preferred_date' => 'date',
            'implant_requirement' => 'boolean',
            'blood_requirement' => 'boolean',
            'specimen_requirement' => 'boolean',
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
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

    public function requestingProvider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'requesting_provider_id');
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(OtProcedure::class, 'procedure_id');
    }

    public function diagnosis(): BelongsTo
    {
        return $this->belongsTo(Diagnosis::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function surgery(): HasOne
    {
        return $this->hasOne(OtSurgery::class, 'surgery_request_id');
    }

    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class);
    }

    public function scopeForTenant($query, int $organizationId, ?int $branchId = null)
    {
        return $query->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
