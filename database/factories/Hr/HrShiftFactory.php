<?php

namespace Database\Factories\Hr;

use App\Models\Hr\HrShift;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<HrShift> */
class HrShiftFactory extends Factory
{
    protected $model = HrShift::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'code' => strtoupper(fake()->unique()->lexify('SH???')), 'name' => fake()->word().' shift', 'start_time' => '08:00', 'end_time' => '16:00', 'break_minutes' => 30, 'grace_minutes' => 10, 'is_night' => false, 'status' => 'active'];
    }
}
