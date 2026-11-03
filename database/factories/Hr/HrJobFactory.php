<?php

namespace Database\Factories\Hr;

use App\Models\Hr\HrJob;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<HrJob> */
class HrJobFactory extends Factory
{
    protected $model = HrJob::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'code' => strtoupper(fake()->unique()->lexify('JOB???')), 'title' => fake()->jobTitle(), 'family' => 'admin', 'status' => 'active'];
    }
}
