<?php

namespace Database\Factories;

use App\Models\AppointmentSlot;
use App\Models\Branch;
use App\Models\Company;
use App\Models\DoctorSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentSlotFactory extends Factory
{
    protected $model = AppointmentSlot::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'branch_id' => Branch::factory(),
            'doctor_id' => User::factory(),
            'schedule_id' => DoctorSchedule::factory(),
            'slot_datetime' => fake()->dateTimeBetween('now', '+30 days'),
            'duration_minutes' => 30,
            'max_capacity' => 1,
            'booked_count' => 0,
            'status' => 'available',
        ];
    }
}
