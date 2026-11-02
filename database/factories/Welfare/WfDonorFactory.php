<?php

namespace Database\Factories\Welfare;

use App\Models\Organization;
use App\Models\Welfare\WfDonor;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WfDonor> */
class WfDonorFactory extends Factory
{
    protected $model = WfDonor::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'name' => fake()->name(), 'kind' => 'individual', 'anonymous' => false];
    }
}
