<?php

namespace App\Policies;

use App\Models\Laboratory\LabQcRun;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LabQcPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('lab.qc.view');
    }

    public function view(User $user, LabQcRun $run): bool
    {
        return $user->can('lab.qc.view') && $this->inScope($user, $run);
    }

    public function create(User $user): bool
    {
        return $user->can('lab.qc.create');
    }

    private function inScope(User $user, LabQcRun $run): bool
    {
        return $user->companies()->where('companies.id', $run->company_id)->exists();
    }
}
