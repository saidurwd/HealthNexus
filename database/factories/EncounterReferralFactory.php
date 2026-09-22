<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EncounterReferralFactory extends Factory
{
    protected $model = \App\Models\EncounterReferral::class;

    public function definition(): array
    {
        return [
            'encounter_id' => Encounter::factory(),
            'patient_id' => Patient::factory(),
            'company_id' => Company::factory(),
            'referral_type' => fake()->randomElement(['internal', 'external', 'department', 'provider']),
            'referred_to' => fake()->optional()->word(),
            'referred_by' => fake()->optional()->word(),
            'reason' => fake()->optional()->sentence(),
            'notes' => fake()->optional()->sentence(),
            'status' => 'pending',
            'created_by' => User::factory(),
        ];
    }
}
