<?php

namespace App\Policies;

use App\Models\Nursing\NursingAssignment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NursingAssignmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('nursing.assignment.view');
    }

    public function view(User $user, NursingAssignment $assignment): bool
    {
        return $user->can('nursing.assignment.view') && $this->inScope($user, $assignment);
    }

    public function create(User $user): bool
    {
        return $user->can('nursing.assignment.create');
    }

    public function update(User $user, NursingAssignment $assignment): bool
    {
        return $user->can('nursing.assignment.update') && $this->inScope($user, $assignment);
    }

    private function inScope(User $user, NursingAssignment $assignment): bool
    {
        return $user->companies()->where('companies.id', $assignment->episode->company_id)->exists();
    }
}
