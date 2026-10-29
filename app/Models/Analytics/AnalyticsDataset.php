<?php

namespace App\Models\Analytics;

use App\Models\Concerns\HasPublicId;
use Database\Factories\Analytics\AnalyticsDatasetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalyticsDataset extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): AnalyticsDatasetFactory
    {
        return AnalyticsDatasetFactory::new();
    }

    protected $table = 'analytics_datasets';

    protected $guarded = ['id'];

    protected $casts = ['definition' => 'array', 'decided_at' => 'datetime', 'generated_at' => 'datetime'];
}
