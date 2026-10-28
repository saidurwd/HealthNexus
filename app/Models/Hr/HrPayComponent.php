<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;

class HrPayComponent extends Model
{
    protected $table = 'hr_pay_components';

    protected $guarded = ['id'];

    protected $casts = ['is_basic' => 'boolean', 'taxable' => 'boolean', 'prorate' => 'boolean'];
}
