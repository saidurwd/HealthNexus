<?php

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Model;

class AnalyticsDqRule extends Model
{
    protected $table = 'analytics_dq_rules';

    protected $guarded = ['id'];

    protected $casts = ['active' => 'boolean'];
}
