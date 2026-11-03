<?php

namespace Database\Factories\Finance;

use App\Models\Finance\Account;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Account> */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'code' => (string) fake()->unique()->numberBetween(6000, 9999), 'name' => ucfirst(fake()->words(2, true)), 'type' => 'expense', 'normal_side' => 'debit', 'postable' => true, 'active' => true];
    }

    public function type(string $type): static
    {
        return $this->state(['type' => $type, 'normal_side' => in_array($type, ['asset', 'expense'], true) ? 'debit' : 'credit']);
    }

    public function heading(): static
    {
        return $this->state(['postable' => false]);
    }
}
