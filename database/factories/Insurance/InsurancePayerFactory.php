<?php

namespace Database\Factories\Insurance;

use App\Models\Insurance\InsurancePayer;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InsurancePayer> */
class InsurancePayerFactory extends Factory
{
    protected $model = InsurancePayer::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(), 'code' => strtoupper(fake()->unique()->lexify('PAY???')), 'name' => fake()->company().' Insurance',
            'payer_type' => 'private_insurer', 'coding_system' => 'icd10', 'submission_method' => 'file_export', 'payment_terms_days' => 30, 'status' => 'active',
        ];
    }

    public function type(string $type): static
    {
        return $this->state(['payer_type' => $type]);
    }
}
