<?php

namespace App\Models\Ipd;

use App\Models\Branch;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class IpdFloor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['organization_id', 'branch_id', 'building_id', 'code', 'name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(IpdBuilding::class, 'building_id');
    }

    public function wards(): HasMany
    {
        return $this->hasMany(IpdWard::class, 'floor_id');
    }

    public function scopeForTenant($query, int $organizationId, ?int $branchId = null)
    {
        return $query->where('organization_id', $organizationId)->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'));
    }
}
