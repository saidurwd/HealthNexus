<?php

namespace Database\Factories;

use App\Models\Encounter;
use App\Models\EncounterHistory;
use App\Models\Organization;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

class EncounterHistoryFactory extends Factory
{
    protected $model = EncounterHistory::class;

    public function definition(): array
    {
        return [
            'encounter_id' => Encounter::factory(),
            'patient_id' => Patient::factory(),
            'organization_id' => Organization::factory(),
            'history_type' => fake()->randomElement(['hpi', 'medical', 'surgical', 'family', 'social', 'medication']),
            'onset' => fake()->optional()->word(),
            'duration' => fake()->optional()->word(),
            'course' => fake()->optional()->word(),
            'severity' => fake()->optional()->word(),
            'associated_symptoms' => fake()->optional()->sentence(),
            'aggravating_factors' => fake()->optional()->sentence(),
            'relieving_factors' => fake()->optional()->sentence(),
            'clinical_notes' => fake()->optional()->paragraph(),
        ];
    }
}
