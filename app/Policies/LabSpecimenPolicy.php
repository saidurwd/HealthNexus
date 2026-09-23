<?php

namespace App\Policies;

use App\Models\Laboratory\LabSpecimen;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LabSpecimenPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('lab.specimen.view');
    }

    public function view(User $user, LabSpecimen $specimen): bool
    {
        return $user->can('lab.specimen.view') && $this->inScope($user, $specimen);
    }

    public function collect(User $user): bool
    {
        return $user->can('lab.specimen.collect');
    }

    public function receive(User $user, LabSpecimen $specimen): bool
    {
        return $user->can('lab.specimen.receive') && $this->inScope($user, $specimen);
    }

    public function reject(User $user, LabSpecimen $specimen): bool
    {
        return $user->can('lab.specimen.reject') && $this->inScope($user, $specimen);
    }

    private function inScope(User $user, LabSpecimen $specimen): bool
    {
        return $user->companies()->where('companies.id', $specimen->company_id)->exists()
            && $user->branches()->where('branches.id', $specimen->branch_id)->exists();
    }
}
