<?php

namespace App\Integrations\Null;

use App\Contracts\Integration\IntegrationResult;
use App\Contracts\Integration\PaymentGateway;
use Illuminate\Support\Facades\Log;

class NullPaymentGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'none';
    }

    public function initiate(array $request): IntegrationResult
    {
        Log::info('Forward integration called but no payment adapter is installed', ['gateway' => 'PaymentGateway']);

        return IntegrationResult::notEnabled('PaymentGateway');
    }

    public function verify(string $gatewayRef): IntegrationResult
    {
        return IntegrationResult::notEnabled('PaymentGateway');
    }
}
