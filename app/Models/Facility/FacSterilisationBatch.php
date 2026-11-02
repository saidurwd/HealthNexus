<?php

namespace App\Models\Facility;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacSterilisationBatch extends Model
{
    use HasPublicId;

    protected $table = 'fac_sterilisation_batches';

    protected $guarded = ['id'];

    protected $casts = ['started_at' => 'datetime', 'ended_at' => 'datetime', 'released_at' => 'datetime'];

    /** @return HasMany<FacBatchItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(FacBatchItem::class, 'batch_id');
    }

    /** @return BelongsTo<FacAsset, $this> */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(FacAsset::class, 'asset_id');
    }
}
