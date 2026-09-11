<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EncounterFactory extends Factory
{
    protected $model = Encounter::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'branch_id' => Branch::factory(),
            'patient_id' => Patient::factory(),
            'encounter_no' => 'ENC-'.strtoupper(fake()->unique()->bothify('??####')),
            'encounter_type' => fake()->randomElement(['OPD', 'IPD', 'ER', 'LAB', 'RADIOLOGY', 'FOLLOW_UP', 'TELEMEDICINE']),
            'attending_doctor_id' => User::factory(),
            'department_id' => Department::factory(),
            'started_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'ended_at' => fake()->optional()->dateTimeBetween('-1 year', 'now'),
            'status' => fake()->randomElement(['active', 'completed', 'cancelled']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
