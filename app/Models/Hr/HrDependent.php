<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;

class HrDependent extends Model
{
    protected $table = 'hr_dependents';

    protected $guarded = ['id'];

    protected $casts = ['date_of_birth' => 'date:Y-m-d'];
}
