<?php

namespace App\Models\Ipd;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpdBedAllocation extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_RELEASED = 'released';

    public const TYPE_ADMISSION = 'admission';
    public const TYPE_TRANSFER = 'transfer';
    public const TYPE_LEAVE_RETURN = 'leave_return';

    protected $fillable = [
        'company_id', 'branch_id', 'admission_id', 'patient_id', 'bed_id',
        'allocated_at', 'released_at', 'status', 'allocation_type', 'reason',
        'allocated_by', 'released_by',
    ];

    protected function casts(): array
    {
        return ['allocated_at' => 'datetime', 'released_at' => 'datetime'];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function admission(): BelongsTo { return $this->belongsTo(IpdAdmission::class, 'admission_id'); }
    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function bed(): BelongsTo { return $this->belongsTo(IpdBed::class, 'bed_id'); }
    public function allocatedBy(): BelongsTo { return $this->belongsTo(User::class, 'allocated_by'); }
    public function releasedBy(): BelongsTo { return $this->belongsTo(User::class, 'released_by'); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
