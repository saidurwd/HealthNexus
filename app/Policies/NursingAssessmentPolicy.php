<?php

namespace App\Policies;

use App\Models\Nursing\NursingAssessment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NursingAssessmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('nursing.assessment.view');
    }

    public function view(User $user, NursingAssessment $assessment): bool
    {
        return $user->can('nursing.assessment.view') && $this->inScope($user, $assessment);
    }

    public function create(User $user): bool
    {
        return $user->can('nursing.assessment.create');
    }

    public function update(User $user, NursingAssessment $assessment): bool
    {
        return $user->can('nursing.assessment.update') && $this->inScope($user, $assessment);
    }

    public function finalize(User $user, NursingAssessment $assessment): bool
    {
        return $user->can('nursing.assessment.finalize') && $this->inScope($user, $assessment);
    }

    private function inScope(User $user, NursingAssessment $assessment): bool
    {
        return $user->companies()->where('companies.id', $assessment->company_id)->exists()
            && (! $assessment->branch_id || $user->branches()->where('branches.id', $assessment->branch_id)->exists());
    }
}
