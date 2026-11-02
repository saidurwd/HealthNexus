<?php

namespace Database\Factories\Ai;

use App\Models\Ai\AiUseCase;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AiUseCase> */
class AiUseCaseFactory extends Factory
{
    protected $model = AiUseCase::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'code' => fake()->unique()->lexify('uc_????'), 'name' => ucfirst(fake()->words(3, true)), 'intended_use' => fake()->sentence(14), 'risk_class' => 'low', 'data_level' => 'C2', 'status' => 'planned', 'implemented' => false];
    }
}
