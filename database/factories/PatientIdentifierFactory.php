<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Patient;
use App\Models\PatientIdentifier;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientIdentifierFactory extends Factory
{
    protected $model = PatientIdentifier::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'patient_id' => Patient::factory(),
            'identifier_type' => fake()->randomElement(['passport', 'driving_license', 'national_id', 'birth_certificate']),
            'identifier_value' => fake()->unique()->numerify('############'),
            'issuing_authority' => fake()->optional()->company(),
            'issued_at' => fake()->optional()->dateTimeBetween('-10 years', '-1 year'),
            'expires_at' => fake()->optional()->dateTimeBetween('now', '+10 years'),
            'is_primary' => fake()->boolean(20),
        ];
    }
}
