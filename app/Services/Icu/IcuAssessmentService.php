<?php

namespace App\Services\Icu;

use App\Events\Icu\ICUAssessmentCompleted;
use App\Models\Icu\IcuAssessment;
use App\Models\Icu\IcuEpisode;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Row-level lock-after-finalize, mirrors OtPreoperativeAssessmentService/NursingAssessmentService
 * exactly. Sections are narrative values (general/respiratory/cardiovascular/neuro/renal/GI/skin/
 * infection per spec §9), not individually-completable checklist items, so no child items table.
 */
class IcuAssessmentService
{
    public function create(IcuEpisode $episode, array $data, User $user): IcuAssessment
    {
        return IcuAssessment::create([
            ...$data,
            'company_id' => $episode->company_id,
            'branch_id' => $episode->branch_id,
            'episode_id' => $episode->id,
            'patient_id' => $episode->patient_id,
            'status' => IcuAssessment::STATUS_DRAFT,
            'assessed_by' => $user->id,
        ]);
    }

    public function update(IcuAssessment $assessment, array $data): IcuAssessment
    {
        if ($assessment->status === IcuAssessment::STATUS_FINAL) {
            throw ValidationException::withMessages(['assessment' => 'This assessment is already finalized and cannot be edited directly.']);
        }

        $assessment->update($data);

        return $assessment->refresh();
    }

    public function finalize(IcuAssessment $assessment, User $user): IcuAssessment
    {
        if ($assessment->status === IcuAssessment::STATUS_FINAL) {
            throw ValidationException::withMessages(['assessment' => 'This assessment is already finalized.']);
        }

        $assessment->update([
            'status' => IcuAssessment::STATUS_FINAL,
            'finalized_at' => now(),
            'finalized_by' => $user->id,
        ]);

        event(new ICUAssessmentCompleted($assessment));

        return $assessment->refresh();
    }
}
