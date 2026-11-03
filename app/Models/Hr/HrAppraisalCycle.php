<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;

class HrAppraisalCycle extends Model
{
    protected $table = 'hr_appraisal_cycles';

    protected $guarded = ['id'];

    protected $casts = ['period_start' => 'date:Y-m-d', 'period_end' => 'date:Y-m-d'];
}
