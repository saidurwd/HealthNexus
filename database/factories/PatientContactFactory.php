<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Patient;
use App\Models\PatientContact;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientContactFactory extends Factory
{
    protected $model = PatientContact::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'patient_id' => Patient::factory(),
            'name' => fake()->name(),
            'relationship' => fake()->randomElement(['spouse', 'parent', 'child', 'sibling', 'friend', 'other']),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->optional()->safeEmail(),
            'address' => fake()->optional()->address(),
            'is_emergency' => true,
        ];
    }
}
