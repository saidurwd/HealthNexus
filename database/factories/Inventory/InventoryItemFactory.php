<?php

namespace Database\Factories\Inventory;

use App\Models\Inventory\InventoryCategory;
use App\Models\Inventory\InventoryItem;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InventoryItem> */
class InventoryItemFactory extends Factory
{
    protected $model = InventoryItem::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(), 'category_id' => fn (array $a) => InventoryCategory::factory()->create(['organization_id' => $a['organization_id']])->id, 'code' => strtoupper(fake()->unique()->bothify('ITM-####')),
            'name' => fake()->unique()->words(3, true), 'base_uom' => 'each', 'purchase_uom' => 'box', 'purchase_to_base' => 10, 'tracks_batch' => true, 'tracks_expiry' => true, 'issue_rule' => 'fefo',
            'reorder_min' => 20, 'reorder_max' => 100, 'lead_time_days' => 7, 'standard_cost' => 15, 'status' => 'active',
        ];
    }
}
