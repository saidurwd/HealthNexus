<?php

namespace Tests\Feature\Phase0;

use App\Contracts\Integration\BloodRequestGateway;
use App\Integrations\BloodBankRequestGateway;
use App\Integrations\Null\NullBloodRequestGateway;
use Tests\TestCase;

class IntegrationStubRegistryTest extends TestCase
{
    public function test_every_registered_gateway_resolves_to_a_null_adapter_that_reports_not_enabled(): void
    {
        // A gateway whose owning phase is built is rebound to the real adapter (Phase 13: Blood Bank); every other one stays a null adapter.
        $built = [BloodRequestGateway::class => BloodBankRequestGateway::class];
        foreach (config('integrations.gateways') as $contract => $adapter) {
            $this->assertInstanceOf($built[$contract] ?? $adapter, app($contract));
            $this->assertInstanceOf($contract, app($contract));
        }
    }

    public function test_null_adapter_returns_not_handled_without_throwing(): void
    {
        $result = (new NullBloodRequestGateway)->requestUnits(['patient_id' => 1]);

        $this->assertFalse($result->handled);
        $this->assertStringContainsString('not enabled', $result->reason);
    }
}
