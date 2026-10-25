<?php

namespace App\Integrations\Null;

use App\Contracts\Integration\AiProviderGateway;
use App\Contracts\Integration\IntegrationResult;
use Illuminate\Support\Facades\Log;

class NullAiProviderGateway implements AiProviderGateway
{
    private function notEnabled(): IntegrationResult
    {
        Log::info('Forward integration called but module is not enabled', ['gateway' => 'AiProviderGateway', 'module' => 'ai']);

        return IntegrationResult::notEnabled('AiProviderGateway');
    }

    public function suggest(string $task, array $context): IntegrationResult
    {
        return $this->notEnabled();
    }
}
