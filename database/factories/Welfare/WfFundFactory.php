<?php

namespace Database\Factories\Welfare;

use App\Models\Organization;
use App\Models\Welfare\WfFund;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WfFund> */
class WfFundFactory extends Factory
{
    protected $model = WfFund::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'name' => 'Fund '.fake()->unique()->numerify('####'), 'kind' => 'donation', 'status' => 'active'];
    }
}
