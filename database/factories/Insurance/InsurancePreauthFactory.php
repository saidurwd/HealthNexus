<?php

namespace Database\Factories\Insurance;

use App\Models\Billing\BillingInsurancePolicy;
use App\Models\Insurance\InsurancePreauth;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InsurancePreauth> Needs a policy: pass ['policy_id' => …, 'payer_id' => …, 'patient_id' => …, 'organization_id' => …] or use the service. */
class InsurancePreauthFactory extends Factory
{
    protected $model = InsurancePreauth::class;

    public function definition(): array
    {
        return [
            'request_number' => strtoupper(fake()->unique()->bothify('PRE-####-######')), 'service_description' => fake()->sentence(3), 'requested_amount' => '5000.00',
            'authorized_amount' => '0.00', 'consumed_amount' => '0.00', 'status' => 'requested', 'requested_at' => now(),
        ];
    }

    public function approved(string $amount = '5000.00'): static
    {
        return $this->state(['status' => 'approved', 'authorized_amount' => $amount, 'valid_from' => now()->toDateString(), 'valid_to' => now()->addDays(30)->toDateString(), 'decided_at' => now()]);
    }

    public function forPolicy(BillingInsurancePolicy $policy): static
    {
        return $this->state(['organization_id' => $policy->organization_id, 'policy_id' => $policy->id, 'payer_id' => $policy->payer_id, 'patient_id' => $policy->patient_id]);
    }
}
