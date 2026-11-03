<?php

namespace App\Contracts\Integration;

/**
 * National exchange / DGHS (Phase 18). Callers depend on this interface only; a Null adapter is bound until the module
 * exists (guideline 13.4, decision D-13). Module key: interop.
 */
interface NationalExchangeGateway
{
    public function publishRecord(array $record): IntegrationResult;

    public function fetchSharedRecord(string $patientReference): IntegrationResult;
}
