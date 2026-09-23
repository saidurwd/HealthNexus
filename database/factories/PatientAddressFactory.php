<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Patient;
use App\Models\PatientAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientAddressFactory extends Factory
{
    protected $model = PatientAddress::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'patient_id' => Patient::factory(),
            'address_type' => fake()->randomElement(['permanent', 'present', 'work', 'mailing']),
            'line1' => fake()->streetAddress(),
            'city' => fake()->city(),
            'district' => fake()->city(),
            'postal_code' => fake()->postcode(),
            'is_primary' => false,
        ];
    }
}
