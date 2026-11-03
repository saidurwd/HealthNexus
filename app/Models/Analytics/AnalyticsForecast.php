<?php

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Model;

class AnalyticsForecast extends Model
{
    public $timestamps = false;

    protected $table = 'analytics_forecasts';

    protected $guarded = ['id'];

    protected $casts = ['points' => 'array', 'generated_at' => 'datetime', 'error_pct' => 'decimal:2'];
}
