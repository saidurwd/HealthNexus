<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BranchPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Branch $branch): bool
    {
        return $user->branches()->where('branches.id', $branch->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('create branches') || $user->hasRole('super_admin');
    }

    public function update(User $user, Branch $branch): bool
    {
        $access = $user->branches()->where('branches.id', $branch->id)->first()?->pivot?->access_level;

        return in_array($access, ['manager', 'admin', 'owner']) || $user->hasRole('super_admin');
    }

    public function delete(User $user, Branch $branch): bool
    {
        $access = $user->companies()->where('companies.id', $branch->company_id)->first()?->pivot?->access_level;

        return $access === 'owner' || $user->hasRole('super_admin');
    }
}
