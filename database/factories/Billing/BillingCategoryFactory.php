<?php

namespace Database\Factories\Billing;

use App\Models\Billing\BillingCategory;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BillingCategory> */
class BillingCategoryFactory extends Factory
{
    protected $model = BillingCategory::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(), 'branch_id' => null,
            'code' => strtoupper(fake()->unique()->lexify('CAT???')), 'name' => fake()->words(2, true), 'is_active' => true,
        ];
    }
}
