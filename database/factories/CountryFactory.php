<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

class CountryFactory extends Factory
{
    protected $model = Country::class;

    public function definition(): array
    {
        return [
            'name' => fake()->country(),
            'code' => strtoupper(fake()->unique()->countryCode()),
            'currency_code' => fake()->currencyCode(),
            'currency_symbol' => fake()->randomElement(['$', '€', '£', '¥', '₹', '৳', 'C$', 'A$']),
            'phone_code' => '+'.fake()->numerify('###'),
            'timezone' => fake()->timezone(),
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'is_active' => true,
        ];
    }
}
