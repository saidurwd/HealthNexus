<?php

namespace Database\Factories;

use App\Models\IdentificationType;
use Illuminate\Database\Eloquent\Factories\Factory;

class IdentificationTypeFactory extends Factory
{
    protected $model = IdentificationType::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'code' => strtoupper(fake()->unique()->lexify('????')),
            'description' => fake()->sentence(),
            'issuing_authority' => fake()->company(),
            'is_primary' => fake()->boolean(),
            'is_active' => true,
        ];
    }
}
