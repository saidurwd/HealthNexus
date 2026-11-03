<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NumberSeries extends Model
{
    protected $table = 'number_series';

    protected $fillable = ['organization_id', 'hospital_id', 'branch_id', 'series', 'period_key', 'last_value'];
}
