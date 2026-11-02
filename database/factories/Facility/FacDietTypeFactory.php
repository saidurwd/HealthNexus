<?php

namespace Database\Factories\Facility;

use App\Models\Facility\FacDietType;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FacDietType> */
class FacDietTypeFactory extends Factory
{
    protected $model = FacDietType::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'code' => strtoupper(fake()->unique()->lexify('D-????')), 'name' => ucfirst(fake()->words(2, true)).' diet', 'active' => true];
    }
}
