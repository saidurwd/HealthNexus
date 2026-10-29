<?php

namespace App\Models\Analytics;

use App\Models\Concerns\HasPublicId;
use Database\Factories\Analytics\AnalyticsKpiFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalyticsKpi extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): AnalyticsKpiFactory
    {
        return AnalyticsKpiFactory::new();
    }

    protected $table = 'analytics_kpis';

    protected $guarded = ['id'];

    protected $casts = ['definition' => 'array', 'target' => 'decimal:2'];
}
