<?php

namespace App\Policies;

use App\Models\Ot\OtSurgicalSafetyChecklist;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OtSafetyChecklistPolicy
{
    use HandlesAuthorization;

    public function view(User $user, OtSurgicalSafetyChecklist $checklist): bool
    {
        return $user->can('ot.checklist.view') && $this->inScope($user, $checklist);
    }

    public function complete(User $user, OtSurgicalSafetyChecklist $checklist): bool
    {
        return $user->can('ot.checklist.complete') && $this->inScope($user, $checklist);
    }

    public function override(User $user, OtSurgicalSafetyChecklist $checklist): bool
    {
        return $user->can('ot.checklist.override') && $this->inScope($user, $checklist);
    }

    private function inScope(User $user, OtSurgicalSafetyChecklist $checklist): bool
    {
        $surgery = $checklist->surgery;

        return $user->companies()->where('companies.id', $surgery->company_id)->exists()
            && (! $surgery->branch_id || $user->branches()->where('branches.id', $surgery->branch_id)->exists());
    }
}
