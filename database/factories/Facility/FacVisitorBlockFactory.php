<?php

namespace Database\Factories\Facility;

use App\Models\Facility\FacVisitorBlock;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FacVisitorBlock> */
class FacVisitorBlockFactory extends Factory
{
    protected $model = FacVisitorBlock::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'name' => fake()->name(), 'reason' => fake()->sentence(6), 'active' => true];
    }
}
