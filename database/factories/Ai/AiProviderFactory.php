<?php

namespace Database\Factories\Ai;

use App\Models\Ai\AiProvider;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AiProvider> */
class AiProviderFactory extends Factory
{
    protected $model = AiProvider::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'name' => ucfirst(fake()->unique()->words(2, true)), 'kind' => 'rules', 'location' => 'on_premise', 'max_level' => 'C3', 'allows_identifiable' => false, 'status' => 'disabled'];
    }
}
