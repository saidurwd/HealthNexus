<?php

namespace App\Policies;

use App\Models\Laboratory\LabCriticalResultAlert;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LabCriticalResultPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('lab.critical_result.view');
    }

    public function view(User $user, LabCriticalResultAlert $alert): bool
    {
        return $user->can('lab.critical_result.view') && $this->inScope($user, $alert);
    }

    public function acknowledge(User $user, LabCriticalResultAlert $alert): bool
    {
        return $user->can('lab.critical_result.acknowledge') && $this->inScope($user, $alert);
    }

    private function inScope(User $user, LabCriticalResultAlert $alert): bool
    {
        return $user->companies()->where('companies.id', $alert->company_id)->exists()
            && $user->branches()->where('branches.id', $alert->branch_id)->exists();
    }
}
