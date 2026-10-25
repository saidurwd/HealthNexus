<?php

namespace App\Contracts\Integration;

/**
 * Blood Bank (Phase 13). Callers depend on this interface only; a Null adapter is bound until the module
 * exists (guideline 13.4, decision D-13). Module key: bloodbank.
 */
interface BloodRequestGateway
{
    public function requestUnits(array $request): IntegrationResult;

    public function releaseEmergencyUnits(array $request): IntegrationResult;
}
