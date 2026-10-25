<?php

namespace App\Contracts\Integration;

/**
 * AI provider (Phase 20). Callers depend on this interface only; a Null adapter is bound until the module
 * exists (guideline 13.4, decision D-13). Module key: ai.
 */
interface AiProviderGateway
{
    public function suggest(string $task, array $context): IntegrationResult;
}
