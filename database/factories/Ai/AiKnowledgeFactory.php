<?php

namespace Database\Factories\Ai;

use App\Models\Ai\AiKnowledge;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AiKnowledge> */
class AiKnowledgeFactory extends Factory
{
    protected $model = AiKnowledge::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'title' => ucfirst(fake()->words(3, true)), 'body' => fake()->paragraph(3), 'status' => 'draft'];
    }
}
