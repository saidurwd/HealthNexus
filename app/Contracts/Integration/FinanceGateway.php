<?php

namespace App\Contracts\Integration;

/**
 * Finance & Accounting (Phase 21). Callers depend on this interface only; a Null adapter is bound until the module
 * exists (guideline 13.4, decision D-13). Module key: finance.
 */
interface FinanceGateway
{
    public function postJournal(array $journal): IntegrationResult;

    public function postReceivable(array $receivable): IntegrationResult;

    public function postPayable(array $payable): IntegrationResult;
}
