<?php

namespace Database\Factories\Analytics;

use App\Models\Analytics\AnalyticsReport;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AnalyticsReport> */
class AnalyticsReportFactory extends Factory
{
    protected $model = AnalyticsReport::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'name' => ucfirst(fake()->words(3, true)), 'query' => ['fact' => 'revenue', 'measures' => ['net_revenue']], 'shared' => true];
    }
}
