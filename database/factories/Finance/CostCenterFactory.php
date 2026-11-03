<?php

namespace Database\Factories\Finance;

use App\Models\Finance\CostCenter;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CostCenter> */
class CostCenterFactory extends Factory
{
    protected $model = CostCenter::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'code' => strtoupper(fake()->unique()->lexify('???')).fake()->numerify('##'), 'name' => ucfirst(fake()->words(2, true)), 'kind' => 'cost', 'active' => true];
    }
}
