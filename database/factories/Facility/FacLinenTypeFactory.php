<?php

namespace Database\Factories\Facility;

use App\Models\Facility\FacLinenType;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FacLinenType> */
class FacLinenTypeFactory extends Factory
{
    protected $model = FacLinenType::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'code' => strtoupper(fake()->unique()->lexify('L-????')), 'name' => ucfirst(fake()->words(2, true)), 'par_level' => 50, 'active' => true];
    }
}
