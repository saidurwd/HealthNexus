<?php

namespace Database\Factories\Analytics;

use App\Models\Analytics\AnalyticsKpi;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AnalyticsKpi> */
class AnalyticsKpiFactory extends Factory
{
    protected $model = AnalyticsKpi::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'code' => 'kpi_'.fake()->unique()->numerify('####'), 'name' => ucfirst(fake()->words(3, true)), 'description' => null, 'domain' => 'operations', 'unit' => 'count', 'good_direction' => 'up', 'definition' => ['fact' => 'encounters', 'measure' => 'visits', 'filters' => []], 'version' => 1, 'status' => 'active'];
    }
}
