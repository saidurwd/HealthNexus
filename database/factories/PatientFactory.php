<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'enterprise_patient_no' => fake()->unique()->numerify('EPT-########'),
            'national_identifier' => fake()->optional()->numerify('##########'),
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->optional()->firstName(),
            'last_name' => fake()->lastName(),
            'date_of_birth' => fake()->dateTimeBetween('-80 years', '-18 years'),
            'sex' => fake()->randomElement(['M', 'F', 'O']),
            'blood_group' => fake()->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->optional()->safeEmail(),
            'address' => fake()->optional()->address(),
            'city' => fake()->optional()->city(),
            'state' => fake()->optional()->state(),
            'country' => fake()->optional()->country(),
            'postal_code' => fake()->optional()->postcode(),
            'emergency_contact' => fake()->optional()->randomElements(['name' => fake()->name(), 'phone' => fake()->phoneNumber()], 2),
            'notes' => fake()->optional()->sentence(),
            'status' => 'active',
        ];
    }
}
