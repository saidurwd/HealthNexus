<?php

namespace App\Policies;

use App\Models\Nursing\NursingDischargeChecklist;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NursingDischargeChecklistPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('nursing.discharge.view');
    }

    public function view(User $user, NursingDischargeChecklist $checklist): bool
    {
        return $user->can('nursing.discharge.view') && $this->inScope($user, $checklist);
    }

    public function create(User $user): bool
    {
        return $user->can('nursing.discharge.complete');
    }

    public function complete(User $user, NursingDischargeChecklist $checklist): bool
    {
        return $user->can('nursing.discharge.complete') && $this->inScope($user, $checklist);
    }

    private function inScope(User $user, NursingDischargeChecklist $checklist): bool
    {
        return $user->companies()->where('companies.id', $checklist->company_id)->exists()
            && (! $checklist->branch_id || $user->branches()->where('branches.id', $checklist->branch_id)->exists());
    }
}
