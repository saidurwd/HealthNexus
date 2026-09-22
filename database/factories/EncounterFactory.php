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
            'appointment_id' => null,
            'encounter_no' => 'ENC-'.strtoupper(fake()->unique()->bothify('??####')),
            'encounter_type' => fake()->randomElement(['OPD', 'IPD', 'ER', 'LAB', 'RADIOLOGY', 'FOLLOW_UP', 'TELEMEDICINE']),
            'encounter_type_id' => null,
            'provider_id' => User::factory(),
            'department_id' => Department::factory(),
            'specialty_id' => null,
            'encounter_date' => fake()->date(),
            'started_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'ended_at' => fake()->optional()->dateTimeBetween('-1 year', 'now'),
            'status' => 'registered',
            'priority' => fake()->optional()->randomElement(['routine', 'urgent', 'stat']),
            'source' => fake()->optional()->randomElement(['appointment', 'walk_in', 'referral', 'emergency', 'follow_up']),
            'chief_complaint_summary' => fake()->optional()->sentence(),
            'reason_for_visit' => fake()->optional()->sentence(),
            'referred_by' => fake()->optional()->word(),
            'notes' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
            'completed_by' => fake()->optional()->randomElement([User::factory(), null]),
            'completed_at' => fake()->optional()->dateTimeBetween('-1 year', 'now'),
            'locked_at' => null,
            'locked_by' => null,
        ];
    }
}
