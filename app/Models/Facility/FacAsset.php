<?php

namespace App\Models\Facility;

use App\Models\Concerns\HasPublicId;
use App\Models\Department;
use Database\Factories\Facility\FacAssetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacAsset extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): FacAssetFactory
    {
        return FacAssetFactory::new();
    }

    protected $table = 'fac_assets';

    protected $guarded = ['id'];

    protected $casts = ['purchased_on' => 'date:Y-m-d', 'warranty_until' => 'date:Y-m-d', 'retired_on' => 'date:Y-m-d', 'cost' => 'decimal:2'];

    /** @return HasMany<FacMaintenancePlan, $this> */
    public function plans(): HasMany
    {
        return $this->hasMany(FacMaintenancePlan::class, 'asset_id');
    }

    /** @return HasMany<FacWorkOrder, $this> */
    public function workOrders(): HasMany
    {
        return $this->hasMany(FacWorkOrder::class, 'asset_id');
    }

    /** @return HasMany<FacCalibration, $this> */
    public function calibrations(): HasMany
    {
        return $this->hasMany(FacCalibration::class, 'asset_id');
    }

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
}
