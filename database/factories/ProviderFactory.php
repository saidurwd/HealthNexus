<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProviderFactory extends Factory
{
    protected $model = Provider::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => 'Dr. '.fake()->lastName(),
            'provider_type' => 'doctor',
            'status' => 'active',
        ];
    }
}
