<?php

namespace App\Models\Laboratory;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabCriticalResultAlert extends Model
{
    protected $fillable = [
        'organization_id', 'branch_id', 'result_id', 'detected_at', 'notified_to',
        'notification_method', 'acknowledged_by', 'acknowledged_at', 'notes',
    ];

    protected function casts(): array
    {
        return ['detected_at' => 'datetime', 'acknowledged_at' => 'datetime'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(LabResult::class, 'result_id');
    }

    public function notifiedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'notified_to');
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function isAcknowledged(): bool
    {
        return $this->acknowledged_at !== null;
    }

    public function scopeForTenant($query, int $organizationId, ?int $branchId = null)
    {
        return $query->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
