<?php

namespace Database\Factories\Analytics;

use App\Models\Analytics\AnalyticsDataset;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AnalyticsDataset> */
class AnalyticsDatasetFactory extends Factory
{
    protected $model = AnalyticsDataset::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'name' => ucfirst(fake()->words(3, true)), 'purpose' => fake()->sentence(12), 'definition' => ['source' => 'encounters', 'columns' => ['month', 'age_band'], 'from' => '2026-01-01', 'to' => '2026-01-31'], 'status' => 'requested'];
    }
}
