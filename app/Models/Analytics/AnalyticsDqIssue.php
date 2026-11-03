<?php

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Model;

class AnalyticsDqIssue extends Model
{
    protected $table = 'analytics_dq_issues';

    protected $guarded = ['id'];

    protected $casts = ['sample' => 'array', 'first_seen_at' => 'datetime', 'last_seen_at' => 'datetime', 'resolved_at' => 'datetime'];
}
