<?php

namespace App\Models\Ipd;

use App\Models\Branch;
use App\Models\Concerns\HasPublicId;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpdPatientLeave extends Model
{
    use HasFactory, HasPublicId;

    public const STATUS_REQUESTED = 'requested';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_ON_LEAVE = 'on_leave';

    public const STATUS_RETURNED = 'returned';

    public const STATUS_CANCELLED = 'cancelled';

    public const BED_HANDLING_RETAIN = 'retain';

    public const BED_HANDLING_RELEASE = 'release';

    protected $fillable = [
        'organization_id', 'branch_id', 'admission_id', 'patient_id', 'leave_type',
        'requested_at', 'expected_return_at', 'actual_return_at', 'reason', 'status',
        'bed_handling', 'requested_by', 'approved_by',
    ];

    protected function casts(): array
    {
        return ['requested_at' => 'datetime', 'expected_return_at' => 'datetime', 'actual_return_at' => 'datetime'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(IpdAdmission::class, 'admission_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeForTenant($query, int $organizationId, ?int $branchId = null)
    {
        return $query->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
