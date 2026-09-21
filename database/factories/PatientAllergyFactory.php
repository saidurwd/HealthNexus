<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\PatientAllergy;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientAllergyFactory extends Factory
{
    protected $model = PatientAllergy::class;

    public function definition(): array
    {
        return [
            'company_id' => Patient::factory(),
            'patient_id' => Patient::factory(),
            'substance' => fake()->word(),
            'severity' => fake()->randomElement(['mild', 'moderate', 'severe']),
            'reaction' => fake()->randomElement(['rash', 'hives', 'itching', 'swelling', 'anaphylaxis', 'other']),
            'notes' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
