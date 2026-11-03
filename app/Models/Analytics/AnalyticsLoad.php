<?php

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Model;

/** One run of one loader. */
class AnalyticsLoad extends Model
{
    public $timestamps = false;

    protected $table = 'analytics_loads';

    protected $guarded = ['id'];

    protected $casts = ['started_at' => 'datetime', 'finished_at' => 'datetime'];
}
