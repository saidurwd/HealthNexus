<?php

namespace App\Integrations\Null;

use App\Contracts\Integration\NationalExchangeGateway;
use App\Contracts\Integration\IntegrationResult;
use Illuminate\Support\Facades\Log;

class NullNationalExchangeGateway implements NationalExchangeGateway
{
    private function notEnabled(): IntegrationResult
    {
        Log::info('Forward integration called but module is not enabled', ['gateway' => 'NationalExchangeGateway', 'module' => 'interop']);

        return IntegrationResult::notEnabled('NationalExchangeGateway');
    }

    public function publishRecord(array $record): IntegrationResult
    {
        return $this->notEnabled();
    }

    public function fetchSharedRecord(string $patientReference): IntegrationResult
    {
        return $this->notEnabled();
    }
}
