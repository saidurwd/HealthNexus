<?php

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Model;

class AnalyticsKpiVersion extends Model
{
    public $timestamps = false;

    protected $table = 'analytics_kpi_versions';

    protected $guarded = ['id'];

    protected $casts = ['definition' => 'array', 'target' => 'decimal:2', 'changed_at' => 'datetime'];
}
