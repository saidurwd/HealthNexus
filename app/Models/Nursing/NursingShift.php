<?php

namespace App\Models\Nursing;

use Illuminate\Database\Eloquent\Model;

class NursingShift extends Model
{
    protected $fillable = [
        'organization_id', 'branch_id', 'name', 'start_time', 'end_time', 'grace_period_minutes', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function scopeForTenant($query, int $organizationId, ?int $branchId = null)
    {
        return $query->where('organization_id', $organizationId)
            ->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'));
    }
}
