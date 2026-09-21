<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\PatientHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientHistoryFactory extends Factory
{
    protected $model = PatientHistory::class;

    public function definition(): array
    {
        return [
            'company_id' => Patient::factory(),
            'patient_id' => Patient::factory(),
            'condition' => fake()->word(),
            'description' => fake()->optional()->sentence(),
            'diagnosed_at' => fake()->optional()->date(),
            'resolved_at' => fake()->optional()->date(),
            'is_active' => true,
            'recorded_by' => 1,
        ];
    }
}
