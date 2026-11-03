<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\AppointmentToken;
use App\Models\Branch;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentTokenFactory extends Factory
{
    protected $model = AppointmentToken::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'branch_id' => Branch::factory(),
            'appointment_id' => Appointment::factory(),
            'token_number' => 'T-'.fake()->numberBetween(1, 999),
            'counter' => '1',
            'status' => 'waiting',
            'generated_at' => now(),
        ];
    }
}
