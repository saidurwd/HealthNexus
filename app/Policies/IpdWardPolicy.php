<?php

namespace App\Policies;

use App\Models\Ipd\IpdWard;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class IpdWardPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('ipd.ward.view');
    }

    public function view(User $user, IpdWard $ward): bool
    {
        return $user->can('ipd.ward.view') && $this->inScope($user, $ward);
    }

    public function create(User $user): bool
    {
        return $user->can('ipd.ward.create');
    }

    public function update(User $user, IpdWard $ward): bool
    {
        return $user->can('ipd.ward.update') && $this->inScope($user, $ward);
    }

    public function delete(User $user, IpdWard $ward): bool
    {
        return $user->can('ipd.ward.update') && $this->inScope($user, $ward);
    }

    private function inScope(User $user, IpdWard $ward): bool
    {
        return $user->companies()->where('companies.id', $ward->company_id)->exists()
            && (! $ward->branch_id || $user->branches()->where('branches.id', $ward->branch_id)->exists());
    }
}
