<?php

namespace Database\Factories\Analytics;

use App\Models\Analytics\AnalyticsDashboard;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AnalyticsDashboard> */
class AnalyticsDashboardFactory extends Factory
{
    protected $model = AnalyticsDashboard::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'code' => 'd'.fake()->unique()->numerify('####'), 'name' => ucfirst(fake()->words(2, true)), 'role_key' => 'custom', 'is_system' => false, 'shared' => true, 'widgets' => [['type' => 'kpi', 'kpi' => 'net_revenue']]];
    }
}
