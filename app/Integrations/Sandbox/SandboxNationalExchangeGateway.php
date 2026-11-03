<?php

namespace App\Integrations\Sandbox;

use App\Contracts\Integration\IntegrationResult;
use App\Contracts\Integration\NationalExchangeGateway;
use Illuminate\Support\Facades\Cache;

/**
 * A pretend national exchange for development, tests and the demo — the "test client" of guideline override 6. It keeps what was published for a day and hands it back; it talks to nobody. The
 * real adapter depends on DGHS onboarding (C, P-18).
 */
class SandboxNationalExchangeGateway implements NationalExchangeGateway
{
    public function publishRecord(array $record): IntegrationResult
    {
        $patient = collect($record['entry'] ?? [])->pluck('resource')->firstWhere('resourceType', 'Patient');
        if ($patient === null) {
            return IntegrationResult::refused('NationalExchangeGateway', 'the record has no Patient');
        }
        $ref = 'SBX-'.strtoupper(substr(hash('sha256', (string) $patient['id']), 0, 12));
        Cache::put('sandbox-national:'.$ref, $record, now()->addDay());
        Cache::put('sandbox-national-last:'.$patient['id'], $ref, now()->addDay());

        return IntegrationResult::handled(['reference' => $ref, 'entries' => count($record['entry'] ?? [])]);
    }

    public function fetchSharedRecord(string $patientReference): IntegrationResult
    {
        $ref = Cache::get('sandbox-national-last:'.$patientReference);
        $record = $ref ? Cache::get('sandbox-national:'.$ref) : null;

        return $record ? IntegrationResult::handled(['reference' => $ref, 'record' => $record]) : IntegrationResult::refused('NationalExchangeGateway', 'nothing shared for that patient');
    }
}
