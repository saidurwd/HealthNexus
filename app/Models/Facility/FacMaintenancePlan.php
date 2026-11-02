<?php

namespace App\Models\Facility;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacMaintenancePlan extends Model
{
    protected $table = 'fac_maintenance_plans';

    protected $guarded = ['id'];

    protected $casts = ['last_done_on' => 'date:Y-m-d', 'next_due_on' => 'date:Y-m-d', 'active' => 'boolean'];

    /** @return BelongsTo<FacAsset, $this> */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(FacAsset::class, 'asset_id');
    }
}
