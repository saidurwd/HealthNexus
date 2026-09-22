<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EncounterComplaintFactory extends Factory
{
    protected $model = \App\Models\EncounterComplaint::class;

    public function definition(): array
    {
        return [
            'encounter_id' => Encounter::factory(),
            'patient_id' => Patient::factory(),
            'company_id' => Company::factory(),
            'complaint' => fake()->sentence(),
            'duration' => fake()->optional()->numberBetween(1, 30),
            'duration_unit' => fake()->optional()->randomElement(['hours', 'days', 'weeks', 'months']),
            'onset' => fake()->optional()->randomElement(['sudden', 'gradual']),
            'severity' => fake()->optional()->randomElement(['mild', 'moderate', 'severe']),
            'location' => fake()->optional()->word(),
            'notes' => fake()->optional()->sentence(),
            'sort_order' => 0,
        ];
    }
}
