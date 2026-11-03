<?php

namespace Database\Factories;

use App\Models\Encounter;
use App\Models\EncounterComplaint;
use App\Models\Organization;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

class EncounterComplaintFactory extends Factory
{
    protected $model = EncounterComplaint::class;

    public function definition(): array
    {
        return [
            'encounter_id' => Encounter::factory(),
            'patient_id' => Patient::factory(),
            'organization_id' => Organization::factory(),
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
