<?php

namespace App\Integrations\Null;

use App\Contracts\Integration\IntegrationResult;
use App\Contracts\Integration\SmsGateway;
use Illuminate\Support\Facades\Log;

class NullSmsGateway implements SmsGateway
{
    public function send(string $to, string $body, array $options = []): IntegrationResult
    {
        Log::info('Forward integration called but no SMS adapter is installed', ['gateway' => 'SmsGateway']);

        return IntegrationResult::notEnabled('SmsGateway');
    }
}
