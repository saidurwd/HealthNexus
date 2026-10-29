<?php

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Model;

class AnalyticsOptin extends Model
{
    protected $table = 'analytics_optins';

    protected $guarded = ['id'];

    protected $casts = ['benchmark' => 'boolean'];
}
