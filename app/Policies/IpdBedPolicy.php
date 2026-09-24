<?php

namespace App\Policies;

use App\Models\Ipd\IpdBed;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class IpdBedPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('ipd.bed.view');
    }

    public function view(User $user, IpdBed $bed): bool
    {
        return $user->can('ipd.bed.view') && $this->inScope($user, $bed);
    }

    public function create(User $user): bool
    {
        return $user->can('ipd.bed.create');
    }

    public function update(User $user, IpdBed $bed): bool
    {
        return $user->can('ipd.bed.update') && $this->inScope($user, $bed);
    }

    public function block(User $user, IpdBed $bed): bool
    {
        return $user->can('ipd.bed.block') && $this->inScope($user, $bed);
    }

    public function unblock(User $user, IpdBed $bed): bool
    {
        return $user->can('ipd.bed.unblock') && $this->inScope($user, $bed);
    }

    public function reserve(User $user, IpdBed $bed): bool
    {
        return $user->can('ipd.bed.reserve') && $this->inScope($user, $bed);
    }

    public function allocate(User $user, IpdBed $bed): bool
    {
        return $user->can('ipd.bed.allocate') && $this->inScope($user, $bed);
    }

    public function release(User $user, IpdBed $bed): bool
    {
        return $user->can('ipd.bed.release') && $this->inScope($user, $bed);
    }

    private function inScope(User $user, IpdBed $bed): bool
    {
        return $user->companies()->where('companies.id', $bed->company_id)->exists()
            && (! $bed->branch_id || $user->branches()->where('branches.id', $bed->branch_id)->exists());
    }
}
