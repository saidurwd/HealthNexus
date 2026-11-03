<?php

namespace App\Integrations\Null;

use App\Contracts\Integration\BloodRequestGateway;
use App\Contracts\Integration\IntegrationResult;
use Illuminate\Support\Facades\Log;

class NullBloodRequestGateway implements BloodRequestGateway
{
    private function notEnabled(): IntegrationResult
    {
        Log::info('Forward integration called but module is not enabled', ['gateway' => 'BloodRequestGateway', 'module' => 'bloodbank']);

        return IntegrationResult::notEnabled('BloodRequestGateway');
    }

    public function requestUnits(array $request): IntegrationResult
    {
        return $this->notEnabled();
    }

    public function releaseEmergencyUnits(array $request): IntegrationResult
    {
        return $this->notEnabled();
    }
}
