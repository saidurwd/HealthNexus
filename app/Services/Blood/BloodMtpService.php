<?php

namespace App\Services\Blood;

use App\Models\Blood\BloodRequest;
use App\Models\Blood\BloodUnit;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/** Massive transfusion protocol: one call opens the pack (red cells, plasma, platelets) as massive-priority requests and releases what the shelf has, first-expiring first, without cross-matching. */
class BloodMtpService
{
    public function __construct(
        private readonly BloodPolicy $policy,
        private readonly BloodRequestService $requests,
        private readonly BloodIssueService $issues,
        private readonly BloodInventoryService $inventory,
    ) {}

    /** @return array{requests: list<BloodRequest>, issued: int, short: array<string, int>} */
    public function activate(User $by, Patient $patient, string $authorisedBy, string $reason, string $sourceModule = 'ed', ?Encounter $encounter = null): array
    {
        if (strlen(trim($reason)) < 5 || strlen(trim($authorisedBy)) < 2) {
            throw ValidationException::withMessages(['reason' => 'The protocol needs the authorising doctor and a reason.']);
        }

        $universal = $this->policy->get('emergency_release_group');
        $requests = [];
        $issued = 0;
        $short = [];
        foreach ($this->policy->get('mtp_pack') as $component => $count) {
            $request = $this->requests->create($by, $patient, $component, $count, 'massive', $sourceModule, $encounter, ['indication' => "Massive transfusion protocol: $reason"]);
            $requests[] = $request;

            $candidates = match ($this->policy->component($component)['kind']) {
                'red' => $this->inventory->fefo($patient->organization_id, $component, $universal['abo'], $universal['rh']),
                'plasma' => $this->inventory->fefo($patient->organization_id, $component, 'AB'),
                default => $this->inventory->fefo($patient->organization_id, $component),
            };
            $given = 0;
            foreach ($candidates->take($count) as $unit) {
                /** @var BloodUnit $unit */
                $this->issues->emergencyIssue($request, $unit, $by, $authorisedBy, "Massive transfusion protocol: $reason");
                $given++;
            }
            $issued += $given;
            $given < $count && $short[$component] = $count - $given;
        }

        return ['requests' => $requests, 'issued' => $issued, 'short' => $short];
    }
}
