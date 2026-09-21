<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiagnosisFactory extends Factory
{
    protected $model = \App\Models\Diagnosis::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'branch_id' => Branch::factory(),
            'appointment_id' => Appointment::factory(),
            'patient_id' => Patient::factory(),
            'doctor_id' => User::factory(),
            'code_type' => 'ICD-10',
            'code' => 'K'.fake()->numerify('-##'),
            'description' => fake()->sentence(3),
            'status' => fake()->randomElement(['confirmed', 'provisional', 'rule_out', 'resolved']),
            'recorded_by' => User::factory(),
            'recorded_at' => now(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
