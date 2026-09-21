<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word().' '.fake()->randomElement(['Dollar', 'Euro', 'Pound', 'Rupee', 'Yen', 'Dinar', 'Real', 'Peso']),
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'symbol' => fake()->randomElement(['$', '€', '£', '¥', '₹', '৳', 'C$', 'A$', 'R$', '₽']),
            'decimal_places' => fake()->randomElement(['0', '1', '2', '3']),
            'is_active' => true,
        ];
    }
}
