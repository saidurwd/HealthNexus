<?php

namespace App\Services\Nursing;

use App\Models\Nursing\NursingAlert;
use App\Models\Nursing\NursingObservation;
use App\Models\User;

class NursingAlertService
{
    public function raise(NursingObservation $observation, string $description, string $severity = NursingAlert::SEVERITY_MODERATE): NursingAlert
    {
        return NursingAlert::create([
            'company_id' => $observation->company_id,
            'branch_id' => $observation->branch_id,
            'episode_id' => $observation->episode_id,
            'admission_id' => $observation->admission_id,
            'patient_id' => $observation->patient_id,
            'observation_id' => $observation->id,
            'trigger_description' => $description,
            'severity' => $severity,
        ]);
    }

    public function acknowledge(NursingAlert $alert, User $user): NursingAlert
    {
        $alert->update(['acknowledged_at' => now(), 'acknowledged_by' => $user->id]);

        return $alert->refresh();
    }

    public function resolve(NursingAlert $alert, string $action): NursingAlert
    {
        $alert->update(['resolved_at' => now(), 'action' => $action]);

        return $alert->refresh();
    }
}
