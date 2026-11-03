<?php

namespace Database\Factories\Watch;

use App\Models\Organization;
use App\Models\Watch\SvCondition;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SvCondition> */
class SvConditionFactory extends Factory
{
    protected $model = SvCondition::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'code' => 'c'.fake()->unique()->numerify('####'), 'name' => 'Dengue', 'icd_prefixes' => ['A90'], 'window_days' => 7, 'min_cases' => 3, 'spike_factor' => 2.0, 'cluster_cases' => 3, 'severity' => 'warning', 'active' => true];
    }
}
