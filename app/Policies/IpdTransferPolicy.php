<?php

namespace App\Policies;

use App\Models\Ipd\IpdBedMovement;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class IpdTransferPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('ipd.transfer.view');
    }

    public function view(User $user, IpdBedMovement $movement): bool
    {
        return $user->can('ipd.transfer.view') && $this->inScope($user, $movement);
    }

    public function create(User $user): bool
    {
        return $user->can('ipd.transfer.create');
    }

    public function approve(User $user, IpdBedMovement $movement): bool
    {
        return $user->can('ipd.transfer.approve') && $this->inScope($user, $movement);
    }

    public function complete(User $user, IpdBedMovement $movement): bool
    {
        return $user->can('ipd.transfer.complete') && $this->inScope($user, $movement);
    }

    public function cancel(User $user, IpdBedMovement $movement): bool
    {
        return $user->can('ipd.transfer.cancel') && $this->inScope($user, $movement);
    }

    private function inScope(User $user, IpdBedMovement $movement): bool
    {
        return $user->companies()->where('companies.id', $movement->company_id)->exists()
            && $user->branches()->where('branches.id', $movement->branch_id)->exists();
    }
}
