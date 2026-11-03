<?php

namespace Database\Factories\Inventory;

use App\Models\Inventory\InventoryBatch;
use App\Models\Inventory\InventoryItem;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InventoryBatch> */
class InventoryBatchFactory extends Factory
{
    protected $model = InventoryBatch::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(), 'item_id' => fn (array $a) => InventoryItem::factory()->create(['organization_id' => $a['organization_id']])->id, 'batch_no' => strtoupper(fake()->unique()->bothify('LOT-####')),
            'expiry_date' => now()->addMonths(18)->toDateString(), 'received_on' => now()->toDateString(), 'unit_cost' => 15, 'status' => 'active',
        ];
    }
}
