<?php

namespace Database\Factories\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\BankAccount;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BankAccount> */
class BankAccountFactory extends Factory
{
    protected $model = BankAccount::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'account_id' => Account::factory()->type('asset'), 'name' => fake()->company().' account', 'kind' => 'bank', 'bank_name' => fake()->company(), 'number_last4' => fake()->numerify('####'), 'active' => true];
    }
}
