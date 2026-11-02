<?php

namespace App\Models\Facility;

use Illuminate\Database\Eloquent\Model;

class FacWorkOrderPart extends Model
{
    protected $table = 'fac_work_order_parts';

    protected $guarded = ['id'];

    protected $casts = ['quantity' => 'decimal:2', 'unit_cost' => 'decimal:2'];
}
