<?php

namespace App\Policies;

use App\Models\Laboratory\LabResult;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LabResultPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('lab.result.view');
    }

    public function view(User $user, LabResult $result): bool
    {
        return $user->can('lab.result.view') && $this->inScope($user, $result);
    }

    public function create(User $user): bool
    {
        return $user->can('lab.result.create');
    }

    public function validate(User $user, LabResult $result): bool
    {
        return $user->can('lab.result.validate') && $this->inScope($user, $result);
    }

    public function approve(User $user, LabResult $result): bool
    {
        return $user->can('lab.result.approve') && $this->inScope($user, $result);
    }

    public function amend(User $user, LabResult $result): bool
    {
        return $user->can('lab.result.amend') && $this->inScope($user, $result);
    }

    private function inScope(User $user, LabResult $result): bool
    {
        return $user->companies()->where('companies.id', $result->company_id)->exists()
            && $user->branches()->where('branches.id', $result->branch_id)->exists();
    }
}
