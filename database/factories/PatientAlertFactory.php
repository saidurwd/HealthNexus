<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientAlertFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'patient_id' => Patient::factory(),
            'alert_type' => fake()->randomElement(['allergy', 'fall_risk', 'infection', 'vip', 'custom']),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'severity' => fake()->randomElement(['info', 'warning', 'critical']),
            'status' => 'active',
            'start_at' => now(),
            'expires_at' => now()->addYear(),
            'created_by' => User::factory(),
            'resolved_by' => null,
            'resolved_at' => null,
        ];
    }
}
