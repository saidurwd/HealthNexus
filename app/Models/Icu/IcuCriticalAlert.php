<?php

namespace App\Models\Icu;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class IcuCriticalAlert extends Model
{
    public const SEVERITY_LOW = 'low';

    public const SEVERITY_MODERATE = 'moderate';

    public const SEVERITY_HIGH = 'high';

    public const SEVERITY_CRITICAL = 'critical';

    public const ESCALATION_NONE = 'none';

    public const ESCALATION_ESCALATED = 'escalated';

    public const ESCALATION_RESOLVED = 'resolved';

    protected $fillable = [
        'organization_id', 'branch_id', 'episode_id', 'rule', 'severity', 'triggered_value',
        'expected_range', 'source_type', 'source_id', 'triggered_at', 'acknowledged_by',
        'acknowledged_at', 'action', 'escalation_status',
    ];

    protected function casts(): array
    {
        return ['triggered_at' => 'datetime', 'acknowledged_at' => 'datetime'];
    }

    public function episode(): BelongsTo { return $this->belongsTo(IcuEpisode::class, 'episode_id'); }
    public function acknowledgedBy(): BelongsTo { return $this->belongsTo(User::class, 'acknowledged_by'); }
    public function source(): MorphTo { return $this->morphTo(); }
    public function escalations(): HasMany { return $this->hasMany(IcuAlertEscalation::class, 'alert_id'); }

    public function isAcknowledged(): bool { return $this->acknowledged_at !== null; }

    public function scopeForTenant($query, int $organizationId, ?int $branchId = null)
    {
        return $query->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
