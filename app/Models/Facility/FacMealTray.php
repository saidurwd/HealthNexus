<?php

namespace App\Models\Facility;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacMealTray extends Model
{
    protected $table = 'fac_meal_trays';

    protected $guarded = ['id'];

    public $timestamps = false;

    /** @return BelongsTo<FacDietOrder, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(FacDietOrder::class, 'diet_order_id');
    }
}
