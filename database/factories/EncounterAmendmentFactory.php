<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EncounterAmendmentFactory extends Factory
{
    protected $model = \App\Models\EncounterAmendment::class;

    public function definition(): array
    {
        return [
            'encounter_id' => Encounter::factory(),
            'patient_id' => Patient::factory(),
            'company_id' => Company::factory(),
            'amendment_type' => fake()->randomElement(['addendum', 'correction', 'clarification']),
            'reason' => fake()->sentence(),
            'content' => fake()->paragraph(),
            'created_by' => User::factory(),
            'approved_by' => fake()->optional()->randomElement([User::factory(), null]),
            'approved_at' => fake()->optional()->dateTime(),
        ];
    }
}
