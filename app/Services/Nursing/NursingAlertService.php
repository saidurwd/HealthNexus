<?php

namespace App\Services\Nursing;

use App\Models\Nursing\NursingAlert;
use App\Models\Nursing\NursingObservation;
use App\Models\User;
use App\Services\Clinical\Alerts\AlertRequest;
use App\Services\Clinical\Alerts\AlertService;

class NursingAlertService
{
    public function __construct(private readonly AlertService $alerts) {}

    public function raise(NursingObservation $observation, string $description, string $severity = NursingAlert::SEVERITY_MODERATE): NursingAlert
    {
        $alert = NursingAlert::create([
            'organization_id' => $observation->organization_id,
            'branch_id' => $observation->branch_id,
            'episode_id' => $observation->episode_id,
            'admission_id' => $observation->admission_id,
            'patient_id' => $observation->patient_id,
            'observation_id' => $observation->id,
            'trigger_description' => $description,
            'severity' => $severity,
        ]);

        // Nursing keeps its own escalation records, so the unified alert is raised on a policy with no ladder.
        $this->alerts->raise(new AlertRequest(
            organizationId: $alert->organization_id, category: 'nursing_ews', severity: in_array($severity, ['high', 'critical'], true) ? 'critical' : 'warning',
            message: $description, sourceModule: 'nursing', patientId: $alert->patient_id, branchId: $alert->branch_id,
            sourceType: $alert->getMorphClass(), sourceId: $alert->id, escalationPolicy: 'nursing',
        ));

        return $alert;
    }

    public function acknowledge(NursingAlert $alert, User $user): NursingAlert
    {
        $alert->update(['acknowledged_at' => now(), 'acknowledged_by' => $user->id]);
        $this->alerts->acknowledgeBySource('nursing', $alert->getMorphClass(), $alert->id, $user);

        return $alert->refresh();
    }

    public function resolve(NursingAlert $alert, string $action): NursingAlert
    {
        $alert->update(['resolved_at' => now(), 'action' => $action]);

        return $alert->refresh();
    }
}
