<?php

namespace Database\Factories\Inventory;

use App\Models\Inventory\ProcurementSupplier;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProcurementSupplier> */
class ProcurementSupplierFactory extends Factory
{
    protected $model = ProcurementSupplier::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'code' => strtoupper(fake()->unique()->bothify('SUP-###')), 'name' => fake()->unique()->company(), 'status' => 'qualified', 'phone' => fake()->numerify('01#########'), 'payment_terms_days' => 30];
    }
}
