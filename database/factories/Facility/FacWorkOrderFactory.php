<?php

namespace Database\Factories\Facility;

use App\Models\Facility\FacWorkOrder;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FacWorkOrder> */
class FacWorkOrderFactory extends Factory
{
    protected $model = FacWorkOrder::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'number' => 'WO-'.fake()->unique()->numerify('######'), 'kind' => 'breakdown', 'title' => ucfirst(fake()->words(4, true)), 'priority' => 'normal', 'status' => 'open', 'reported_at' => now()];
    }
}
