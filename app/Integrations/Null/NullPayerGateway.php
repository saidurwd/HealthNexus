<?php

namespace App\Integrations\Null;

use App\Contracts\Integration\PayerGateway;
use App\Contracts\Integration\IntegrationResult;
use Illuminate\Support\Facades\Log;

class NullPayerGateway implements PayerGateway
{
    private function notEnabled(): IntegrationResult
    {
        Log::info('Forward integration called but module is not enabled', ['gateway' => 'PayerGateway', 'module' => 'insurance']);

        return IntegrationResult::notEnabled('PayerGateway');
    }

    public function checkEligibility(array $request): IntegrationResult
    {
        return $this->notEnabled();
    }

    public function submitPreAuthorization(array $request): IntegrationResult
    {
        return $this->notEnabled();
    }

    public function submitClaim(array $claim): IntegrationResult
    {
        return $this->notEnabled();
    }
}
