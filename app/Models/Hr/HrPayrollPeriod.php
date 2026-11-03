<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;

class HrPayrollPeriod extends Model
{
    protected $table = 'hr_payroll_periods';

    protected $guarded = ['id'];

    protected $casts = ['month_start' => 'date:Y-m-d', 'month_end' => 'date:Y-m-d'];
}
