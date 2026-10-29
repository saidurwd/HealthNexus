<?php

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Model;

class AnalyticsKpiTarget extends Model
{
    protected $table = 'analytics_kpi_targets';

    protected $guarded = ['id'];

    protected $casts = ['target' => 'decimal:2'];
}
