<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Patient;
use App\Models\PatientGuardian;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientGuardianFactory extends Factory
{
    protected $model = PatientGuardian::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'patient_id' => Patient::factory(),
            'name' => fake()->name(),
            'relationship' => fake()->randomElement(['Father', 'Mother', 'Sibling', 'Spouse']),
            'phone' => fake()->phoneNumber(),
            'is_primary' => false,
        ];
    }
}
