<?php

namespace Database\Factories\Ai;

use App\Models\Ai\AiIncident;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AiIncident> */
class AiIncidentFactory extends Factory
{
    protected $model = AiIncident::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'severity' => 'low', 'kind' => 'other', 'description' => fake()->sentence(12), 'status' => 'open'];
    }
}
