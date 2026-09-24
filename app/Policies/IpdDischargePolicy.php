<?php

namespace App\Policies;

use App\Models\Ipd\IpdDischargeRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class IpdDischargePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('ipd.discharge.view');
    }

    public function view(User $user, IpdDischargeRequest $request): bool
    {
        return $user->can('ipd.discharge.view') && $this->inScope($user, $request);
    }

    public function create(User $user): bool
    {
        return $user->can('ipd.discharge.create');
    }

    public function approve(User $user, IpdDischargeRequest $request): bool
    {
        return $user->can('ipd.discharge.approve') && $this->inScope($user, $request);
    }

    public function complete(User $user, IpdDischargeRequest $request): bool
    {
        return $user->can('ipd.discharge.complete') && $this->inScope($user, $request);
    }

    private function inScope(User $user, IpdDischargeRequest $request): bool
    {
        return $user->companies()->where('companies.id', $request->company_id)->exists()
            && $user->branches()->where('branches.id', $request->branch_id)->exists();
    }
}
