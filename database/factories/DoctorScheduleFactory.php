<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use App\Models\DoctorSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DoctorScheduleFactory extends Factory
{
    protected $model = DoctorSchedule::class;

    public function definition(): array
    {
        $startTime = fake()->time('H:i', '08:00');
        $endTime = fake()->time('H:i', '17:00');

        return [
            'company_id' => Company::factory(),
            'branch_id' => Branch::factory(),
            'doctor_id' => User::factory(),
            'name' => 'Dr. '.fake()->name().' Schedule',
            'description' => fake()->sentence(),
            'day_of_week' => fake()->numberBetween(0, 6),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'slot_duration_minutes' => fake()->randomElement([15, 20, 30, 45, 60]),
            'is_active' => true,
        ];
    }
}
