<?php

namespace App\Models\Ipd;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpdBedMovement extends Model
{
    use HasFactory;

    public const TYPE_ADMISSION = 'admission';
    public const TYPE_TRANSFER = 'transfer';
    public const TYPE_TEMPORARY_LEAVE = 'temporary_leave';
    public const TYPE_RETURN = 'return';
    public const TYPE_DISCHARGE = 'discharge';
    public const TYPE_BED_CHANGE = 'bed_change';
    public const TYPE_WARD_CHANGE = 'ward_change';
    public const TYPE_ROOM_CHANGE = 'room_change';
    public const TYPE_ICU_TRANSFER = 'icu_transfer';
    public const TYPE_ISOLATION_TRANSFER = 'isolation_transfer';

    public const STATUS_REQUESTED = 'requested';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'company_id', 'branch_id', 'admission_id', 'patient_id', 'from_bed_id', 'to_bed_id',
        'movement_type', 'requested_at', 'approved_at', 'moved_at', 'reason', 'status',
        'requested_by', 'approved_by', 'completed_by',
    ];

    protected function casts(): array
    {
        return ['requested_at' => 'datetime', 'approved_at' => 'datetime', 'moved_at' => 'datetime'];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function admission(): BelongsTo { return $this->belongsTo(IpdAdmission::class, 'admission_id'); }
    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function fromBed(): BelongsTo { return $this->belongsTo(IpdBed::class, 'from_bed_id'); }
    public function toBed(): BelongsTo { return $this->belongsTo(IpdBed::class, 'to_bed_id'); }
    public function requestedBy(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function approvedBy(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function completedBy(): BelongsTo { return $this->belongsTo(User::class, 'completed_by'); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
