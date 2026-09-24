<?php

namespace App\Services\Nursing;

use App\Events\Nursing\NursingAssessmentCompleted;
use App\Models\Nursing\NursingAssessment;
use App\Models\Nursing\NursingEpisode;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class NursingAssessmentService
{
    public function create(NursingEpisode $episode, array $data, User $user): NursingAssessment
    {
        return NursingAssessment::create([
            ...$data,
            'company_id' => $episode->company_id,
            'branch_id' => $episode->branch_id,
            'episode_id' => $episode->id,
            'admission_id' => $episode->admission_id,
            'patient_id' => $episode->patient_id,
            'encounter_id' => $episode->encounter_id,
            'status' => NursingAssessment::STATUS_DRAFT,
            'created_by' => $user->id,
        ]);
    }

    public function update(NursingAssessment $assessment, array $data): NursingAssessment
    {
        if ($assessment->status === NursingAssessment::STATUS_FINAL) {
            throw ValidationException::withMessages(['assessment' => 'This assessment is already finalized and cannot be edited directly.']);
        }

        $assessment->update($data);

        return $assessment->refresh();
    }

    public function finalize(NursingAssessment $assessment, User $user): NursingAssessment
    {
        if ($assessment->status === NursingAssessment::STATUS_FINAL) {
            throw ValidationException::withMessages(['assessment' => 'This assessment is already finalized.']);
        }

        $assessment->update([
            'status' => NursingAssessment::STATUS_FINAL,
            'finalized_at' => now(),
            'finalized_by' => $user->id,
        ]);

        event(new NursingAssessmentCompleted($assessment));

        return $assessment->refresh();
    }
}
