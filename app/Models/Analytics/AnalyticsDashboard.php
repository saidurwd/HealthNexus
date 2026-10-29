<?php

namespace App\Models\Analytics;

use App\Models\Concerns\HasPublicId;
use Database\Factories\Analytics\AnalyticsDashboardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalyticsDashboard extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): AnalyticsDashboardFactory
    {
        return AnalyticsDashboardFactory::new();
    }

    protected $table = 'analytics_dashboards';

    protected $guarded = ['id'];

    protected $casts = ['widgets' => 'array', 'is_system' => 'boolean', 'shared' => 'boolean'];
}
