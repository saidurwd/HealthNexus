<?php

namespace App\Models\Facility;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacLinenMovement extends Model
{
    protected $table = 'fac_linen_movements';

    protected $guarded = ['id'];

    public const UPDATED_AT = null;

    protected $casts = ['moved_on' => 'date:Y-m-d'];

    /** @return BelongsTo<FacLinenType, $this> */
    public function type(): BelongsTo
    {
        return $this->belongsTo(FacLinenType::class, 'linen_type_id');
    }
}
