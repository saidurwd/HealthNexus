<?php

namespace App\Models\Ipd;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Organization;
use App\Models\Specialty;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class IpdWard extends Model
{
    use HasFactory, SoftDeletes;

    public const WARD_TYPE_GENERAL = 'general';

    public const WARD_TYPE_ICU = 'icu';

    public const WARD_TYPE_HDU = 'hdu';

    public const WARD_TYPE_PICU = 'picu';

    public const WARD_TYPE_NICU = 'nicu';

    public const WARD_TYPE_CCU = 'ccu';

    protected $fillable = [
        'organization_id', 'branch_id', 'building_id', 'floor_id', 'department_id', 'specialty_id',
        'code', 'name', 'ward_type', 'gender_policy', 'capacity', 'isolation_capable', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'isolation_capable' => 'boolean',
            'is_active' => 'boolean',
        ];
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

    public function floor(): BelongsTo
    {
        return $this->belongsTo(IpdFloor::class, 'floor_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(IpdRoom::class, 'ward_id');
    }

    public function scopeForTenant($query, int $organizationId, ?int $branchId = null)
    {
        return $query->where('organization_id', $organizationId)->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'));
    }

    public function scopeIcuTypes($query)
    {
        return $query->whereIn('ward_type', [
            self::WARD_TYPE_ICU, self::WARD_TYPE_HDU, self::WARD_TYPE_PICU, self::WARD_TYPE_NICU, self::WARD_TYPE_CCU,
        ]);
    }
}
