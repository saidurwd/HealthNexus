<?php

namespace App\Services\Nursing;

use App\Events\Nursing\NursingEscalationAcknowledged;
use App\Events\Nursing\NursingEscalationCreated;
use App\Events\Nursing\NursingEscalationResolved;
use App\Models\Nursing\NursingEpisode;
use App\Models\Nursing\NursingEscalation;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Documents the escalation workflow only — never diagnoses or treats the patient itself
 * (spec §43: "It must not autonomously diagnose the patient").
 */
class NursingEscalationService
{
    public function create(NursingEpisode $episode, array $data, User $user): NursingEscalation
    {
        $escalation = NursingEscalation::create([
            ...$data,
            'company_id' => $episode->company_id,
            'branch_id' => $episode->branch_id,
            'episode_id' => $episode->id,
            'admission_id' => $episode->admission_id,
            'patient_id' => $episode->patient_id,
            'created_by' => $user->id,
        ]);

        event(new NursingEscalationCreated($escalation));

        return $escalation;
    }

    public function acknowledge(NursingEscalation $escalation, User $user): NursingEscalation
    {
        if ($escalation->acknowledged_at) {
            throw ValidationException::withMessages(['escalation' => 'This escalation has already been acknowledged.']);
        }

        $escalation->update(['acknowledged_at' => now(), 'acknowledged_by' => $user->id]);

        event(new NursingEscalationAcknowledged($escalation));

        return $escalation->refresh();
    }

    public function resolve(NursingEscalation $escalation, User $user, string $actionTaken): NursingEscalation
    {
        if ($escalation->resolved_at) {
            throw ValidationException::withMessages(['escalation' => 'This escalation has already been resolved.']);
        }

        $escalation->update(['resolved_at' => now(), 'resolved_by' => $user->id, 'action_taken' => $actionTaken]);

        event(new NursingEscalationResolved($escalation));

        return $escalation->refresh();
    }
}
