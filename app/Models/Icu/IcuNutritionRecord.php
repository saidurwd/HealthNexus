<?php

namespace App\Models\Icu;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IcuNutritionRecord extends Model
{
    protected $fillable = [
        'organization_id', 'branch_id', 'episode_id', 'route', 'feeding_type', 'rate', 'rate_unit',
        'plan', 'dietitian_reviewed', 'recorded_by', 'recorded_at',
    ];

    protected function casts(): array
    {
        return ['rate' => 'float', 'dietitian_reviewed' => 'boolean', 'recorded_at' => 'datetime'];
    }

    public function episode(): BelongsTo { return $this->belongsTo(IcuEpisode::class, 'episode_id'); }
    public function recordedBy(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }

    public function scopeForTenant($query, int $organizationId, ?int $branchId = null)
    {
        return $query->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
