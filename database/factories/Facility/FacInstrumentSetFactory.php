<?php

namespace Database\Factories\Facility;

use App\Models\Facility\FacInstrumentSet;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FacInstrumentSet> */
class FacInstrumentSetFactory extends Factory
{
    protected $model = FacInstrumentSet::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'code' => strtoupper(fake()->unique()->lexify('SET-????')), 'name' => ucfirst(fake()->words(3, true)), 'instrument_count' => 12, 'active' => true];
    }
}
