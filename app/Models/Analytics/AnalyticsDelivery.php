<?php

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Model;

class AnalyticsDelivery extends Model
{
    public $timestamps = false;

    protected $table = 'analytics_deliveries';

    protected $guarded = ['id'];

    protected $casts = ['run_at' => 'datetime'];
}
