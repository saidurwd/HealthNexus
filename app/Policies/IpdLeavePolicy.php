<?php

namespace App\Policies;

use App\Models\Ipd\IpdPatientLeave;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class IpdLeavePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('ipd.leave.view');
    }

    public function view(User $user, IpdPatientLeave $leave): bool
    {
        return $user->can('ipd.leave.view') && $this->inScope($user, $leave);
    }

    public function create(User $user): bool
    {
        return $user->can('ipd.leave.create');
    }

    public function approve(User $user, IpdPatientLeave $leave): bool
    {
        return $user->can('ipd.leave.approve') && $this->inScope($user, $leave);
    }

    public function complete(User $user, IpdPatientLeave $leave): bool
    {
        return $user->can('ipd.leave.complete') && $this->inScope($user, $leave);
    }

    private function inScope(User $user, IpdPatientLeave $leave): bool
    {
        return $user->companies()->where('companies.id', $leave->company_id)->exists()
            && $user->branches()->where('branches.id', $leave->branch_id)->exists();
    }
}
