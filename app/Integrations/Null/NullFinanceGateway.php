<?php

namespace App\Integrations\Null;

use App\Contracts\Integration\FinanceGateway;
use App\Contracts\Integration\IntegrationResult;
use Illuminate\Support\Facades\Log;

class NullFinanceGateway implements FinanceGateway
{
    private function notEnabled(): IntegrationResult
    {
        Log::info('Forward integration called but module is not enabled', ['gateway' => 'FinanceGateway', 'module' => 'finance']);

        return IntegrationResult::notEnabled('FinanceGateway');
    }

    public function postJournal(array $journal): IntegrationResult
    {
        return $this->notEnabled();
    }

    public function postReceivable(array $receivable): IntegrationResult
    {
        return $this->notEnabled();
    }

    public function postPayable(array $payable): IntegrationResult
    {
        return $this->notEnabled();
    }
}
