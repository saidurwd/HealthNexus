<?php

namespace Database\Factories;

use App\Models\Hospital;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class HospitalFactory extends Factory
{
    protected $model = Hospital::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->city().' Hospital',
            'code' => strtoupper(fake()->unique()->lexify('HOS???')),
            'slug' => fake()->unique()->slug(),
            'is_active' => true,
        ];
    }
}
