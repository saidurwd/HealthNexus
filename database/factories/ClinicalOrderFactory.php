<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClinicalOrderFactory extends Factory
{
    protected $model = \App\Models\ClinicalOrder::class;

    public function definition(): array
    {
        $company = Company::factory();
        $prefix = strtoupper(fake()->unique()->bothify('??'));

        return [
            'company_id' => $company,
            'branch_id' => Branch::factory(),
            'order_number' => $prefix.'-ORD-'.str_pad(fake()->unique()->randomNumber(5), 8, '0', STR_PAD_LEFT),
            'patient_id' => Patient::factory(),
            'encounter_id' => Encounter::factory(),
            'provider_id' => User::factory(),
            'order_type' => fake()->randomElement(['laboratory', 'radiology', 'procedure', 'other']),
            'priority' => fake()->randomElement(['routine', 'urgent', 'stat']),
            'status' => 'requested',
            'ordered_at' => now(),
            'ordered_by' => User::factory(),
            'cancelled_at' => null,
            'cancelled_by' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
