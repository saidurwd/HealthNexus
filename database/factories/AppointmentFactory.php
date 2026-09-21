<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'branch_id' => Branch::factory(),
            'patient_id' => Patient::factory(),
            'doctor_id' => User::factory(),
            'appointment_no' => strtoupper(fake()->unique()->lexify('APT-????')).'-'.fake()->numberBetween(1000, 9999),
            'appointment_date' => fake()->date(),
            'appointment_time' => fake()->time('H:i', '09:00'),
            'status' => 'scheduled',
            'type' => fake()->randomElement(['scheduled', 'followup', 'walkin']),
            'source' => fake()->randomElement(['online', 'phone', 'walkin']),
            'reason' => fake()->sentence(),
        ];
    }
}
