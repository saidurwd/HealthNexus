<?php

namespace App\Services\Nursing;

use App\Events\Nursing\NursingDischargeChecklistCompleted;
use App\Models\Ipd\IpdDischargeRequest;
use App\Models\Nursing\NursingDischargeChecklist;
use App\Models\Nursing\NursingEpisode;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Nursing clearance only (spec §49) — never writes to ipd_discharge_requests itself; Phase 8
 * remains the operational discharge owner and reads this checklist's status independently.
 */
class NursingDischargeChecklistService
{
    public function create(NursingEpisode $episode, ?IpdDischargeRequest $dischargeRequest = null): NursingDischargeChecklist
    {
        return NursingDischargeChecklist::firstOrCreate(
            ['episode_id' => $episode->id],
            [
                'company_id' => $episode->company_id,
                'branch_id' => $episode->branch_id,
                'admission_id' => $episode->admission_id,
                'patient_id' => $episode->patient_id,
                'discharge_request_id' => $dischargeRequest?->id,
                'status' => NursingDischargeChecklist::STATUS_PENDING,
            ],
        );
    }

    public function updateItem(NursingDischargeChecklist $checklist, array $data): NursingDischargeChecklist
    {
        if ($checklist->status === NursingDischargeChecklist::STATUS_COMPLETED) {
            throw ValidationException::withMessages(['checklist' => 'This discharge checklist is already completed.']);
        }

        $checklist->update($data);

        return $checklist->refresh();
    }

    public function complete(NursingDischargeChecklist $checklist, User $user): NursingDischargeChecklist
    {
        if ($checklist->status === NursingDischargeChecklist::STATUS_COMPLETED) {
            throw ValidationException::withMessages(['checklist' => 'This discharge checklist is already completed.']);
        }

        $required = [
            'education_completed', 'medication_education_completed', 'devices_removed',
            'belongings_confirmed', 'follow_up_instructions_given',
        ];

        foreach ($required as $field) {
            if (! $checklist->{$field}) {
                throw ValidationException::withMessages(['checklist' => "All checklist items must be confirmed before completion ('{$field}' is not yet confirmed)."]);
            }
        }

        $checklist->update([
            'status' => NursingDischargeChecklist::STATUS_COMPLETED,
            'completed_by' => $user->id,
            'completed_at' => now(),
        ]);

        event(new NursingDischargeChecklistCompleted($checklist));

        return $checklist->refresh();
    }
}
