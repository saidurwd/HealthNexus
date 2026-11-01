<?php

namespace Database\Factories\Quality;

use App\Models\Organization;
use App\Models\Quality\QaStandard;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<QaStandard> */
class QaStandardFactory extends Factory
{
    protected $model = QaStandard::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'code' => strtoupper(fake()->unique()->lexify('STD-????')), 'name' => ucfirst(fake()->words(3, true)), 'active' => true];
    }
}
