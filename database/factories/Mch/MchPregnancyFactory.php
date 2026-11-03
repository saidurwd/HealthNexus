<?php

namespace Database\Factories\Mch;

use App\Models\Mch\MchPregnancy;
use App\Models\Organization;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MchPregnancy> */
class MchPregnancyFactory extends Factory
{
    protected $model = MchPregnancy::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'number' => 'PR-'.fake()->unique()->numerify('2026-#####'), 'patient_id' => Patient::factory(), 'lmp' => now()->subWeeks(20)->toDateString(), 'edd' => now()->addWeeks(20)->toDateString(), 'gravida' => 1, 'para' => 0, 'status' => 'active', 'booked_at' => now()->subWeeks(8)];
    }
}
