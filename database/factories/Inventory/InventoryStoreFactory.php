<?php

namespace Database\Factories\Inventory;

use App\Models\Inventory\InventoryStore;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InventoryStore> */
class InventoryStoreFactory extends Factory
{
    protected $model = InventoryStore::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'code' => strtoupper(fake()->unique()->bothify('ST-###')), 'name' => fake()->words(2, true).' store', 'kind' => 'central', 'allow_negative' => false, 'is_active' => true];
    }
}
