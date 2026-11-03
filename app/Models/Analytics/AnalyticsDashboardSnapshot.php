<?php

namespace App\Models\Analytics;

use App\Models\Concerns\HasPublicId;
use Database\Factories\Analytics\AnalyticsDashboardSnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** A dashboard as it looked for a period. Written once; there is no screen that edits one. */
class AnalyticsDashboardSnapshot extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): AnalyticsDashboardSnapshotFactory
    {
        return AnalyticsDashboardSnapshotFactory::new();
    }

    protected $table = 'analytics_dashboard_snapshots';

    protected $guarded = ['id'];

    protected $casts = ['widgets' => 'array', 'period_from' => 'date:Y-m-d', 'period_to' => 'date:Y-m-d'];
}
