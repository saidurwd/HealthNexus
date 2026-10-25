<?php

namespace App\Services\Ot;

use App\Events\Ot\PreoperativeAssessmentCompleted;
use App\Models\Ot\OtPreoperativeAssessment;
use App\Models\Ot\OtSurgery;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Row-level lock-after-finalize, mirrors NursingAssessmentService exactly.
 */
class OtPreoperativeAssessmentService
{
    public function create(OtSurgery $surgery, array $data, User $user): OtPreoperativeAssessment
    {
        return OtPreoperativeAssessment::create([
            ...$data,
            'company_id' => $surgery->company_id,
            'branch_id' => $surgery->branch_id,
            'surgery_id' => $surgery->id,
            'patient_id' => $surgery->patient_id,
            'status' => OtPreoperativeAssessment::STATUS_DRAFT,
            'assessed_by' => $user->id,
        ]);
    }

    public function update(OtPreoperativeAssessment $assessment, array $data): OtPreoperativeAssessment
    {
        if ($assessment->status === OtPreoperativeAssessment::STATUS_FINAL) {
            throw ValidationException::withMessages(['assessment' => 'This assessment is already finalized and cannot be edited directly.']);
        }

        $assessment->update($data);

        return $assessment->refresh();
    }

    public function finalize(OtPreoperativeAssessment $assessment, User $user): OtPreoperativeAssessment
    {
        if ($assessment->status === OtPreoperativeAssessment::STATUS_FINAL) {
            throw ValidationException::withMessages(['assessment' => 'This assessment is already finalized.']);
        }

        $assessment->update([
            'status' => OtPreoperativeAssessment::STATUS_FINAL,
            'finalized_at' => now(),
            'finalized_by' => $user->id,
        ]);

        event(new PreoperativeAssessmentCompleted($assessment));

        return $assessment->refresh();
    }
}
