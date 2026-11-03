<?php

namespace App\Models\Facility;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacBatchItem extends Model
{
    protected $table = 'fac_batch_items';

    protected $guarded = ['id'];

    protected $casts = ['use_by' => 'date:Y-m-d', 'issued_at' => 'datetime'];

    /** @return BelongsTo<FacSterilisationBatch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(FacSterilisationBatch::class, 'batch_id');
    }

    /** @return BelongsTo<FacInstrumentSet, $this> */
    public function set(): BelongsTo
    {
        return $this->belongsTo(FacInstrumentSet::class, 'set_id');
    }
}
