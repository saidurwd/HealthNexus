<?php

namespace Database\Factories\Welfare;

use App\Models\Organization;
use App\Models\Welfare\WfFund;
use App\Models\Welfare\WfFundEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WfFundEntry> */
class WfFundEntryFactory extends Factory
{
    protected $model = WfFundEntry::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'fund_id' => WfFund::factory(), 'entry_type' => 'donation', 'amount' => 1000, 'occurred_at' => now()];
    }
}
