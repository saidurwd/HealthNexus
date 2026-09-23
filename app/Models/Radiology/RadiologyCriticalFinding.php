<?php

namespace App\Models\Radiology;

use App\Models\Provider;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadiologyCriticalFinding extends Model
{
    protected $fillable = [
        'company_id', 'branch_id', 'report_id', 'finding_text', 'detected_by', 'detected_at',
        'notified_to', 'notification_method', 'notified_at', 'acknowledged_by', 'acknowledged_at',
        'notes', 'status',
    ];

    protected function casts(): array
    {
        return ['detected_at' => 'datetime', 'notified_at' => 'datetime', 'acknowledged_at' => 'datetime'];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function report(): BelongsTo { return $this->belongsTo(RadiologyReport::class, 'report_id'); }
    public function detectedBy(): BelongsTo { return $this->belongsTo(Provider::class, 'detected_by'); }
    public function notifiedTo(): BelongsTo { return $this->belongsTo(User::class, 'notified_to'); }
    public function acknowledgedBy(): BelongsTo { return $this->belongsTo(User::class, 'acknowledged_by'); }

    public function isAcknowledged(): bool { return $this->acknowledged_at !== null; }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
