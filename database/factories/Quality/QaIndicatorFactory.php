<?php

namespace Database\Factories\Quality;

use App\Models\Quality\QaIndicator;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<QaIndicator> */
class QaIndicatorFactory extends Factory
{
    protected $model = QaIndicator::class;

    public function definition(): array
    {
        return ['organization_id' => null, 'code' => fake()->unique()->lexify('ind_????'), 'name' => ucfirst(fake()->words(3, true)), 'domain' => 'safety', 'unit' => 'count', 'good_direction' => 'down', 'formula' => 'falls_per_1000', 'active' => true];
    }
}
