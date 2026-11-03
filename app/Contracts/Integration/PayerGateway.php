<?php

namespace App\Contracts\Integration;

/**
 * Insurance payer APIs (Phase 14). Callers depend on this interface only; a Null adapter is bound until the module
 * exists (guideline 13.4, decision D-13). Module key: insurance.
 */
interface PayerGateway
{
    public function checkEligibility(array $request): IntegrationResult;

    public function submitPreAuthorization(array $request): IntegrationResult;

    public function submitClaim(array $claim): IntegrationResult;
}
