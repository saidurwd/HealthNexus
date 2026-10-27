<?php

namespace Database\Factories\Insurance;

use App\Models\Insurance\InsurancePayer;
use App\Models\Insurance\InsurancePlan;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InsurancePlan> */
class InsurancePlanFactory extends Factory
{
    protected $model = InsurancePlan::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'payer_id' => fn (array $a) => InsurancePayer::factory()->create(['organization_id' => $a['organization_id']])->id, 'code' => strtoupper(fake()->unique()->lexify('PLN???')), 'name' => fake()->randomElement(['Silver', 'Gold', 'Platinum', 'Basic']).' plan',
            'copay_percent' => 20, 'deductible_amount' => 0, 'annual_limit' => 200000, 'status' => 'active',
        ];
    }
}
