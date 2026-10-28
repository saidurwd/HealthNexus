<?php

namespace Database\Factories\Hr;

use App\Models\Hr\HrEmployee;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<HrEmployee> */
class HrEmployeeFactory extends Factory
{
    protected $model = HrEmployee::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'employee_no' => 'EMP-'.fake()->unique()->numerify('#####'), 'first_name' => fake()->firstName(), 'last_name' => fake()->lastName(), 'gender' => fake()->randomElement(['male', 'female']), 'category' => 'permanent', 'status' => 'active', 'joined_on' => now()->subYear()->toDateString(), 'weekly_off' => 'fri'];
    }
}
