<?php

namespace Database\Factories\Billing;

use App\Models\Billing\BillingCategory;
use App\Models\Billing\BillingItem;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BillingItem> */
class BillingItemFactory extends Factory
{
    protected $model = BillingItem::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(), 'branch_id' => null,
            'category_id' => fn (array $attrs) => BillingCategory::factory()->create(['organization_id' => $attrs['organization_id']])->id,
            'item_code' => strtoupper(fake()->unique()->bothify('ITEM-####')), 'item_type' => 'service', 'name' => fake()->words(3, true), 'unit' => 'each',
            'base_price' => fake()->randomElement(['150.00', '300.00', '500.00', '1200.00']), 'is_taxable' => false, 'is_clinically_chargeable' => false, 'is_active' => true,
        ];
    }

    /** Charged automatically when the clinical event happens (e.g. lab_test / HGB). */
    public function forEvent(string $type, string $key): static
    {
        return $this->state(['is_clinically_chargeable' => true, 'clinical_event_type' => $type, 'clinical_event_key' => $key]);
    }
}
