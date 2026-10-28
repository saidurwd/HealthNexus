<?php

namespace App\Integrations\Null;

use App\Contracts\Integration\BiometricGateway;
use App\Contracts\Integration\IntegrationResult;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;

class NullBiometricGateway implements BiometricGateway
{
    public function pullPunches(string $deviceCode, CarbonInterface $since): IntegrationResult
    {
        Log::info('Forward integration called but no device adapter is installed', ['gateway' => 'BiometricGateway', 'device' => $deviceCode]);

        return IntegrationResult::notEnabled('BiometricGateway');
    }
}
