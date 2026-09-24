<?php

namespace App\Models\Ipd;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpdBedBlock extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_ENDED = 'ended';

    protected $fillable = [
        'company_id', 'branch_id', 'bed_id', 'reason_type', 'reason', 'start_at',
        'expected_end_at', 'actual_end_at', 'status', 'requested_by', 'approved_by',
    ];

    protected function casts(): array
    {
        return ['start_at' => 'datetime', 'expected_end_at' => 'datetime', 'actual_end_at' => 'datetime'];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function bed(): BelongsTo { return $this->belongsTo(IpdBed::class, 'bed_id'); }
    public function requestedBy(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function approvedBy(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
