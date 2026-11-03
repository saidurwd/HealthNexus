<?php

namespace App\Integrations\Null;

use App\Contracts\Integration\IntegrationResult;
use App\Contracts\Integration\TelehealthGateway;
use Illuminate\Support\Facades\Log;

class NullTelehealthGateway implements TelehealthGateway
{
    public function createRoom(array $request): IntegrationResult
    {
        Log::info('Forward integration called but no video adapter is installed', ['gateway' => 'TelehealthGateway']);

        return IntegrationResult::notEnabled('TelehealthGateway');
    }

    public function closeRoom(string $roomRef): IntegrationResult
    {
        return IntegrationResult::notEnabled('TelehealthGateway');
    }
}
