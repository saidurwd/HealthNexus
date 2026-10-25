<?php

namespace App\Models\Icu;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IcuFluidBalanceEntry extends Model
{
    public const TYPE_INTAKE = 'intake';

    public const TYPE_OUTPUT = 'output';

    protected $fillable = [
        'company_id', 'branch_id', 'episode_id', 'type', 'category', 'volume_ml', 'recorded_at', 'recorded_by',
    ];

    protected function casts(): array
    {
        return ['volume_ml' => 'float', 'recorded_at' => 'datetime'];
    }

    public function episode(): BelongsTo { return $this->belongsTo(IcuEpisode::class, 'episode_id'); }
    public function recordedBy(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
