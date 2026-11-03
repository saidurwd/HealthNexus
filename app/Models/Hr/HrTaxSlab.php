<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;

class HrTaxSlab extends Model
{
    protected $table = 'hr_tax_slabs';

    protected $guarded = ['id'];

    protected $casts = ['effective_from' => 'date:Y-m-d', 'from_amount' => 'decimal:2', 'to_amount' => 'decimal:2', 'rate_percent' => 'decimal:3'];
}
