<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientProblemFactory extends Factory
{
    protected $model = \App\Models\PatientProblem::class;

    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'company_id' => Company::factory(),
            'problem_code' => fake()->optional()->bothify('??##'),
            'problem_name' => fake()->sentence(3),
            'coding_system' => fake()->optional()->randomElement(['ICD-10', 'ICD-11', 'SNOMED CT']),
            'status' => fake()->randomElement(['active', 'resolved']),
            'onset_date' => fake()->optional()->date(),
            'resolved_date' => fake()->optional()->date(),
            'source_encounter_id' => fake()->optional()->randomElement([Encounter::factory(), null]),
            'notes' => fake()->optional()->paragraph(),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
}
