<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class VitalSignFactory extends Factory
{
    protected $model = \App\Models\VitalSign::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'branch_id' => Branch::factory(),
            'appointment_id' => Appointment::factory(),
            'patient_id' => Patient::factory(),
            'recorded_by' => User::factory(),
            'recorded_at' => now(),
            'temperature' => fake()->randomFloat(2, 35.0, 42.0),
            'systolic' => fake()->numberBetween(90, 180),
            'diastolic' => fake()->numberBetween(60, 120),
            'pulse_rate' => fake()->numberBetween(50, 130),
            'respiratory_rate' => fake()->numberBetween(10, 30),
            'height' => fake()->randomFloat(2, 140, 220),
            'weight' => fake()->randomFloat(2, 40, 150),
            'oxygen_saturation' => fake()->numberBetween(85, 100),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
