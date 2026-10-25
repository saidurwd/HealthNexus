<?php

namespace App\Models\Ed;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EdZone extends Model
{
    use HasPublicId;

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['is_active' => 'boolean'];

    /** @return HasMany<EdBay, $this> */
    public function bays(): HasMany
    {
        return $this->hasMany(EdBay::class, 'zone_id');
    }

    public function scopeForTenant($query, int $organizationId, ?int $branchId = null)
    {
        return $query->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where(fn ($q2) => $q2->whereNull('branch_id')->orWhere('branch_id', $branchId)));
    }
}
