<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;

class HrLeaveType extends Model
{
    protected $table = 'hr_leave_types';

    protected $guarded = ['id'];

    protected $casts = ['is_paid' => 'boolean', 'annual_days' => 'decimal:2', 'carry_forward_max' => 'decimal:2', 'encashable' => 'boolean', 'requires_hr_approval' => 'boolean'];
}
