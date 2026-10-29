<?php

namespace Database\Factories\Integration;

use App\Models\Integration\IntegrationPartner;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<IntegrationPartner> */
class IntegrationPartnerFactory extends Factory
{
    protected $model = IntegrationPartner::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'code' => strtoupper(fake()->unique()->lexify('????')).fake()->numerify('##'), 'name' => fake()->company().' (test partner)', 'kind' => 'fhir_client', 'direction' => 'both',
            'status' => 'active', 'profile' => 'r4', 'requires_consent' => true, 'consent_type' => 'data_sharing'];
    }

    public function kind(string $kind): static
    {
        return $this->state(['kind' => $kind]);
    }

    public function noConsentNeeded(): static
    {
        return $this->state(['requires_consent' => false, 'consent_type' => null]);
    }
}
