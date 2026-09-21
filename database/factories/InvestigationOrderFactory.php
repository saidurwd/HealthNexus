<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvestigationOrderFactory extends Factory
{
    protected $model = \App\Models\InvestigationOrder::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'branch_id' => Branch::factory(),
            'appointment_id' => Appointment::factory(),
            'patient_id' => Patient::factory(),
            'doctor_id' => User::factory(),
            'order_no' => 'INV-'.fake()->numberBetween(1000, 9999),
            'test_name' => fake()->randomElement(['CBC', 'Blood Sugar', 'Lipid Profile', 'Liver Function', 'Kidney Function', 'X-Ray Chest', 'ECG']),
            'category' => fake()->randomElement(['Laboratory', 'Radiology', 'Cardiology']),
            'clinical_notes' => fake()->sentence(),
            'priority' => fake()->randomElement(['routine', 'urgent', 'stat']),
            'status' => 'ordered',
            'ordered_by' => User::factory(),
            'ordered_at' => now(),
        ];
    }
}
