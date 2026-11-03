<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;

class HrPayrollLineItem extends Model
{
    protected $table = 'hr_payroll_line_items';

    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = ['amount' => 'decimal:2'];
}
