<?php

namespace App\Integrations\Sandbox;

use App\Contracts\Integration\IntegrationResult;
use App\Contracts\Integration\PaymentGateway;
use Illuminate\Support\Facades\Cache;

/**
 * A pretend aggregator for development, tests and the demo — it moves no money. A reference ending in "-FAIL" fails, one ending in "-HOLD" stays pending, anything else succeeds.
 * The same idempotency key always returns the same gateway reference.
 */
class SandboxPaymentGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'sandbox';
    }

    public function initiate(array $request): IntegrationResult
    {
        $ref = 'SBX-'.strtoupper(substr(hash('sha256', $request['idempotency_key']), 0, 14));
        $outcome = str_ends_with($request['reference'], '-FAIL') ? 'failed' : (str_ends_with($request['reference'], '-HOLD') ? 'pending' : 'succeeded');
        Cache::forever('sandbox-payment:'.$ref, ['status' => $outcome, 'amount' => $request['amount']]);

        return IntegrationResult::handled(['gateway_ref' => $ref, 'redirect_url' => 'https://sandbox.invalid/pay/'.$ref]);
    }

    public function verify(string $gatewayRef): IntegrationResult
    {
        $seen = Cache::get('sandbox-payment:'.$gatewayRef);

        return $seen === null ? IntegrationResult::refused('PaymentGateway', 'unknown payment') : IntegrationResult::handled($seen);
    }
}
