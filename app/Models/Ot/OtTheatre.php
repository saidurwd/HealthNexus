<?php

namespace App\Models\Ot;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OtTheatre extends Model
{
    public const STATUS_AVAILABLE = 'available';

    public const STATUS_OCCUPIED = 'occupied';

    public const STATUS_CLEANING = 'cleaning';

    public const STATUS_MAINTENANCE = 'maintenance';

    public const STATUS_BLOCKED = 'blocked';

    protected $fillable = [
        'company_id', 'branch_id', 'complex_id', 'theatre_code', 'name', 'location',
        'capacity', 'emergency_capable', 'status', 'is_active',
    ];

    protected function casts(): array
    {
        return ['emergency_capable' => 'boolean', 'is_active' => 'boolean'];
    }

    public function complex(): BelongsTo
    {
        return $this->belongsTo(OtComplex::class, 'complex_id');
    }

    public function capabilities(): HasMany
    {
        return $this->hasMany(OtTheatreCapability::class, 'theatre_id');
    }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)
            ->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'));
    }
}
