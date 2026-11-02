<?php

namespace Database\Factories\Facility;

use App\Models\Facility\FacMortuaryUnit;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FacMortuaryUnit> */
class FacMortuaryUnitFactory extends Factory
{
    protected $model = FacMortuaryUnit::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'code' => strtoupper(fake()->unique()->lexify('MU-???')), 'name' => 'Chamber '.fake()->numberBetween(1, 9), 'capacity' => 4, 'active' => true];
    }
}
