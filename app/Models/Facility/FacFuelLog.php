<?php

namespace App\Models\Facility;

use Illuminate\Database\Eloquent\Model;

class FacFuelLog extends Model
{
    protected $table = 'fac_fuel_logs';

    protected $guarded = ['id'];

    protected $casts = ['filled_on' => 'date:Y-m-d', 'litres' => 'decimal:2', 'cost' => 'decimal:2'];
}
