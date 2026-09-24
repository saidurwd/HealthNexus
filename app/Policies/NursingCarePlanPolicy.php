<?php

namespace App\Policies;

use App\Models\Nursing\NursingCarePlan;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NursingCarePlanPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('nursing.care_plan.view');
    }

    public function view(User $user, NursingCarePlan $carePlan): bool
    {
        return $user->can('nursing.care_plan.view') && $this->inScope($user, $carePlan);
    }

    public function create(User $user): bool
    {
        return $user->can('nursing.care_plan.create');
    }

    public function update(User $user, NursingCarePlan $carePlan): bool
    {
        return $user->can('nursing.care_plan.update') && $this->inScope($user, $carePlan);
    }

    public function complete(User $user, NursingCarePlan $carePlan): bool
    {
        return $user->can('nursing.care_plan.complete') && $this->inScope($user, $carePlan);
    }

    private function inScope(User $user, NursingCarePlan $carePlan): bool
    {
        return $user->companies()->where('companies.id', $carePlan->company_id)->exists()
            && (! $carePlan->branch_id || $user->branches()->where('branches.id', $carePlan->branch_id)->exists());
    }
}
