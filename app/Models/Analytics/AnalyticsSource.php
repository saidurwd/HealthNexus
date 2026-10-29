<?php

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Model;

/** One thing the warehouse loads: a dimension or a fact, with the place it stopped (the watermark). */
class AnalyticsSource extends Model
{
    protected $table = 'analytics_sources';

    protected $guarded = ['id'];

    protected $casts = ['active' => 'boolean', 'last_run_at' => 'datetime'];
}
