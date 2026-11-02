<?php

namespace App\Models\Facility;

use Illuminate\Database\Eloquent\Model;

class FacWasteRecord extends Model
{
    protected $table = 'fac_waste_records';

    protected $guarded = ['id'];

    protected $casts = ['recorded_on' => 'date:Y-m-d', 'weight_kg' => 'decimal:2'];
}
