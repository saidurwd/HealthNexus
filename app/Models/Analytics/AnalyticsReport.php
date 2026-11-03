<?php

namespace App\Models\Analytics;

use App\Models\Concerns\HasPublicId;
use Database\Factories\Analytics\AnalyticsReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalyticsReport extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): AnalyticsReportFactory
    {
        return AnalyticsReportFactory::new();
    }

    protected $table = 'analytics_reports';

    protected $guarded = ['id'];

    protected $casts = ['query' => 'array', 'shared' => 'boolean'];
}
