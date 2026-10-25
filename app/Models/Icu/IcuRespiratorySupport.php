<?php

namespace App\Models\Icu;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IcuRespiratorySupport extends Model
{
    protected $fillable = [
        'organization_id', 'branch_id', 'episode_id', 'device', 'flow_rate', 'fio2', 'reason',
        'response', 'started_at', 'stopped_at', 'started_by', 'stopped_by',
    ];

    protected function casts(): array
    {
        return ['flow_rate' => 'float', 'fio2' => 'float', 'started_at' => 'datetime', 'stopped_at' => 'datetime'];
    }

    public function episode(): BelongsTo { return $this->belongsTo(IcuEpisode::class, 'episode_id'); }
    public function startedBy(): BelongsTo { return $this->belongsTo(User::class, 'started_by'); }
    public function stoppedBy(): BelongsTo { return $this->belongsTo(User::class, 'stopped_by'); }

    public function scopeForTenant($query, int $organizationId, ?int $branchId = null)
    {
        return $query->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
