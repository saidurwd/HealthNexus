<?php

namespace Database\Factories\Vital;

use App\Models\Organization;
use App\Models\Patient;
use App\Models\Vital\VrDeathCertification;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VrDeathCertification> */
class VrDeathCertificationFactory extends Factory
{
    protected $model = VrDeathCertification::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'number' => 'DC-'.fake()->unique()->numerify('2026-#####'), 'patient_id' => Patient::factory(), 'died_at' => now()->subHours(5), 'place_of_death' => 'this_hospital', 'manner' => 'natural', 'cause_a' => 'Septic shock', 'interval_a' => '2 days', 'status' => 'draft'];
    }
}
