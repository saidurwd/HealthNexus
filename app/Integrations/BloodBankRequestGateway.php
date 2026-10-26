<?php

namespace App\Integrations;

use App\Contracts\Integration\BloodRequestGateway;
use App\Contracts\Integration\IntegrationResult;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\User;
use App\Services\Blood\BloodMtpService;
use App\Services\Blood\BloodRequestService;
use App\Services\ModuleManager;
use Illuminate\Validation\ValidationException;

/**
 * The real Blood Bank behind the forward-integration gateway (guideline 13.4). ED, ICU, OT, IPD and Nursing call BloodRequestGateway only; when the
 * module is off for the organization the call is answered "not enabled" instead of failing.
 *
 * requestUnits: patient_id, user_id, component, units, priority?, source_module?, encounter_id?, indication?
 * releaseEmergencyUnits: patient_id, user_id, authorised_by, reason, source_module?, encounter_id?
 */
class BloodBankRequestGateway implements BloodRequestGateway
{
    public function __construct(private readonly BloodRequestService $requests, private readonly BloodMtpService $mtp, private readonly ModuleManager $modules) {}

    public function requestUnits(array $request): IntegrationResult
    {
        [$patient, $user] = $this->actors($request);
        if (! $this->modules->isEnabled('bloodbank', $patient->organization_id)) {
            return IntegrationResult::notEnabled('BloodRequestGateway');
        }
        $encounter = isset($request['encounter_id']) ? Encounter::find($request['encounter_id']) : null;

        try {
            $created = $this->requests->create($user, $patient, $request['component'], (int) $request['units'], $request['priority'] ?? 'routine', $request['source_module'] ?? 'opd', $encounter, ['indication' => $request['indication'] ?? null]);
        } catch (ValidationException $e) {
            return IntegrationResult::refused('BloodRequestGateway', collect($e->errors())->flatten()->implode(' '));
        }

        return IntegrationResult::handled(['request_id' => $created->public_id, 'request_number' => $created->request_number, 'status' => $created->status]);
    }

    public function releaseEmergencyUnits(array $request): IntegrationResult
    {
        [$patient, $user] = $this->actors($request);
        if (! $this->modules->isEnabled('bloodbank', $patient->organization_id)) {
            return IntegrationResult::notEnabled('BloodRequestGateway');
        }
        $encounter = isset($request['encounter_id']) ? Encounter::find($request['encounter_id']) : null;

        try {
            $result = $this->mtp->activate($user, $patient, $request['authorised_by'] ?? '', $request['reason'] ?? '', $request['source_module'] ?? 'ed', $encounter);
        } catch (ValidationException $e) {
            return IntegrationResult::refused('BloodRequestGateway', collect($e->errors())->flatten()->implode(' '));
        }

        return IntegrationResult::handled(['requests' => collect($result['requests'])->pluck('request_number')->all(), 'issued' => $result['issued'], 'short' => $result['short']]);
    }

    /** @return array{0: Patient, 1: User} */
    private function actors(array $request): array
    {
        return [Patient::findOrFail($request['patient_id']), User::findOrFail($request['user_id'])];
    }
}
