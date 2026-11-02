<?php

namespace App\Models\Facility;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacCalibration extends Model
{
    protected $table = 'fac_calibrations';

    protected $guarded = ['id'];

    protected $casts = ['performed_on' => 'date:Y-m-d', 'next_due_on' => 'date:Y-m-d'];

    /** @return BelongsTo<FacAsset, $this> */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(FacAsset::class, 'asset_id');
    }
}
