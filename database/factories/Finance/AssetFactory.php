<?php

namespace Database\Factories\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\Asset;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Asset> */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'tag' => 'EQ-'.fake()->unique()->numerify('####'), 'name' => ucfirst(fake()->words(2, true)), 'acquired_on' => '2026-01-01', 'cost' => '120000.00', 'salvage_value' => '0.00', 'life_months' => 60, 'depreciation_from' => '2026-01-01', 'accumulated' => '0.00', 'status' => 'active',
            'asset_account_id' => Account::factory()->type('asset'), 'accumulated_account_id' => Account::factory()->type('asset'), 'expense_account_id' => Account::factory()->type('expense')];
    }
}
