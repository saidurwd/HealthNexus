<?php

namespace Database\Factories;

use App\Models\ClinicalOrder;
use App\Models\ClinicalOrderItem;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClinicalOrderItemFactory extends Factory
{
    protected $model = ClinicalOrderItem::class;

    public function definition(): array
    {
        return [
            'clinical_order_id' => ClinicalOrder::factory(),
            'organization_id' => Organization::factory(),
            'item_name' => fake()->word(),
            'item_code' => fake()->optional()->bothify('??##'),
            'quantity' => fake()->optional()->numberBetween(1, 5),
            'notes' => fake()->optional()->sentence(),
            'status' => 'pending',
        ];
    }
}
