<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrescriptionFactory extends Factory
{
    protected $model = Prescription::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'branch_id' => Branch::factory(),
            'appointment_id' => Appointment::factory(),
            'patient_id' => Patient::factory(),
            'doctor_id' => User::factory(),
            'prescription_no' => 'RX-'.fake()->numberBetween(1000, 9999),
            'clinical_notes' => fake()->sentence(),
            'advice' => fake()->sentence(),
            'created_by' => User::factory(),
            'prescribed_at' => now(),
        ];
    }
}
