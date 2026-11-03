<?php

namespace Database\Factories\Analytics;

use App\Models\Analytics\AnalyticsDashboardSnapshot;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AnalyticsDashboardSnapshot> */
class AnalyticsDashboardSnapshotFactory extends Factory
{
    protected $model = AnalyticsDashboardSnapshot::class;

    public function definition(): array
    {
        $widgets = [['type' => 'table', 'title' => 'Example', 'kind' => null, 'query' => ['fact' => 'revenue'], 'data' => ['columns' => [], 'rows' => [], 'meta' => []], 'run_query' => ['fact' => 'revenue']]];

        return ['organization_id' => Organization::factory(), 'dashboard_code' => 'ceo', 'dashboard_name' => 'Chief executive', 'period_from' => '2026-10-01', 'period_to' => '2026-10-31', 'kind' => 'monthly',
            'widgets' => $widgets, 'checksum' => hash('sha256', json_encode($widgets))];
    }
}
