<?php

namespace Tests\Feature\Phase0;

use App\Contracts\Integration\BloodRequestGateway;
use Tests\TestCase;

class IntegrationStubRegistryTest extends TestCase
{
    public function test_every_registered_gateway_resolves_to_a_null_adapter_that_reports_not_enabled(): void
    {
        foreach (config('integrations.gateways') as $contract => $adapter) {
            $this->assertInstanceOf($adapter, app($contract));
            $this->assertInstanceOf($contract, app($contract));
        }
    }

    public function test_null_adapter_returns_not_handled_without_throwing(): void
    {
        $result = app(BloodRequestGateway::class)->requestUnits(['patient_id' => 1]);

        $this->assertFalse($result->handled);
        $this->assertStringContainsString('not enabled', $result->reason);
    }
}
