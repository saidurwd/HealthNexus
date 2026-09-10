<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'code' => strtoupper(fake()->unique()->lexify('COMP???')),
            'slug' => fake()->unique()->slug(),
            'email' => fake()->optional()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'address' => fake()->optional()->address(),
            'logo' => fake()->optional()->imageUrl(),
            'settings' => [],
            'is_active' => true,
            'trial_ends_at' => fake()->optional()->dateTimeBetween('now', '+30 days'),
            'subscription_ends_at' => fake()->optional()->dateTimeBetween('now', '+1 year'),
        ];
    }
}
