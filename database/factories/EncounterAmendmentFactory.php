<?php

namespace Database\Factories;

use App\Models\Encounter;
use App\Models\EncounterAmendment;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EncounterAmendmentFactory extends Factory
{
    protected $model = EncounterAmendment::class;

    public function definition(): array
    {
        return [
            'encounter_id' => Encounter::factory(),
            'patient_id' => Patient::factory(),
            'organization_id' => Organization::factory(),
            'amendment_type' => fake()->randomElement(['addendum', 'correction', 'clarification']),
            'reason' => fake()->sentence(),
            'content' => fake()->paragraph(),
            'created_by' => User::factory(),
            'approved_by' => fake()->optional()->randomElement([User::factory(), null]),
            'approved_at' => fake()->optional()->dateTime(),
        ];
    }
}
