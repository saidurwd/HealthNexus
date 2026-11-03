<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;

class HrEmployeeHistory extends Model
{
    protected $table = 'hr_employee_history';

    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = ['from_value' => 'array', 'to_value' => 'array', 'effective_on' => 'date:Y-m-d'];
}
