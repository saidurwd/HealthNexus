<?php

namespace App\Policies;

use App\Models\Ot\OtPreoperativeAssessment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OtPreoperativeAssessmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('ot.preop.view');
    }

    public function view(User $user, OtPreoperativeAssessment $assessment): bool
    {
        return $user->can('ot.preop.view') && $this->inScope($user, $assessment);
    }

    public function create(User $user): bool
    {
        return $user->can('ot.preop.create');
    }

    public function update(User $user, OtPreoperativeAssessment $assessment): bool
    {
        return $user->can('ot.preop.create') && $this->inScope($user, $assessment);
    }

    public function complete(User $user, OtPreoperativeAssessment $assessment): bool
    {
        return $user->can('ot.preop.complete') && $this->inScope($user, $assessment);
    }

    private function inScope(User $user, OtPreoperativeAssessment $assessment): bool
    {
        return $user->companies()->where('companies.id', $assessment->company_id)->exists()
            && (! $assessment->branch_id || $user->branches()->where('branches.id', $assessment->branch_id)->exists());
    }
}
