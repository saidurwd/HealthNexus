<?php

namespace Database\Factories\Inventory;

use App\Models\Inventory\InventoryCategory;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InventoryCategory> */
class InventoryCategoryFactory extends Factory
{
    protected $model = InventoryCategory::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'code' => strtoupper(fake()->unique()->lexify('CAT???')), 'name' => fake()->words(2, true), 'kind' => 'consumable', 'is_active' => true];
    }
}
