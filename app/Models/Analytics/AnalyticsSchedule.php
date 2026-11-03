<?php

namespace App\Models\Analytics;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsSchedule extends Model
{
    use HasPublicId;

    protected $table = 'analytics_schedules';

    protected $guarded = ['id'];

    protected $casts = ['recipients' => 'array', 'active' => 'boolean', 'next_run_at' => 'datetime', 'last_run_at' => 'datetime'];

    /** @return BelongsTo<AnalyticsReport, $this> */
    public function report(): BelongsTo
    {
        return $this->belongsTo(AnalyticsReport::class, 'report_id');
    }
}
