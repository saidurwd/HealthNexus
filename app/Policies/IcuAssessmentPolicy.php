<?php

namespace App\Policies;

use App\Models\Icu\IcuAssessment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class IcuAssessmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('icu.assessment.view');
    }

    public function view(User $user, IcuAssessment $assessment): bool
    {
        return $user->can('icu.assessment.view') && $this->inScope($user, $assessment);
    }

    public function create(User $user): bool
    {
        return $user->can('icu.assessment.create');
    }

    public function update(User $user, IcuAssessment $assessment): bool
    {
        return $user->can('icu.assessment.update') && $this->inScope($user, $assessment);
    }

    public function finalize(User $user, IcuAssessment $assessment): bool
    {
        return $user->can('icu.assessment.finalize') && $this->inScope($user, $assessment);
    }

    private function inScope(User $user, IcuAssessment $assessment): bool
    {
        return $user->companies()->where('companies.id', $assessment->company_id)->exists()
            && (! $assessment->branch_id || $user->branches()->where('branches.id', $assessment->branch_id)->exists());
    }
}
