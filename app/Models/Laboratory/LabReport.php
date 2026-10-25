<?php

namespace App\Models\Laboratory;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabReport extends Model
{
    protected $fillable = [
        'organization_id', 'branch_id', 'lab_order_id', 'report_number', 'status',
        'generated_by', 'generated_at', 'amended_by', 'amended_at',
        'cancelled_by', 'cancelled_at', 'cancellation_reason',
    ];

    protected function casts(): array
    {
        return ['generated_at' => 'datetime', 'amended_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }

    public function organization(): BelongsTo { return $this->belongsTo(\App\Models\Organization::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function labOrder(): BelongsTo { return $this->belongsTo(LabOrder::class, 'lab_order_id'); }
    public function generatedBy(): BelongsTo { return $this->belongsTo(User::class, 'generated_by'); }
    public function amendedBy(): BelongsTo { return $this->belongsTo(User::class, 'amended_by'); }
    public function cancelledBy(): BelongsTo { return $this->belongsTo(User::class, 'cancelled_by'); }

    public function isFinal(): bool { return in_array($this->status, ['final', 'amended'], true); }
    public function isPreliminary(): bool { return $this->status === 'preliminary'; }
    public function isCancelled(): bool { return $this->status === 'cancelled'; }

    public function scopeForTenant($query, int $organizationId, ?int $branchId = null)
    {
        return $query->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
