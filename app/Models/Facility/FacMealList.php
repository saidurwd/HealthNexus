<?php

namespace App\Models\Facility;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacMealList extends Model
{
    use HasPublicId;

    protected $table = 'fac_meal_lists';

    protected $guarded = ['id'];

    protected $casts = ['service_date' => 'date:Y-m-d', 'served_at' => 'datetime'];

    /** @return HasMany<FacMealTray, $this> */
    public function trays(): HasMany
    {
        return $this->hasMany(FacMealTray::class, 'meal_list_id');
    }
}
