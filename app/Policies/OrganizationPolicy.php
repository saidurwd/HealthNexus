<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrganizationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Organization $organization): bool
    {
        return $user->organizations()->where('organizations.id', $organization->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('create organizations') || $user->hasRole('super_admin');
    }

    public function update(User $user, Organization $organization): bool
    {
        $access = $user->organizations()->where('organizations.id', $organization->id)->first()?->pivot?->access_level;

        return in_array($access, ['owner', 'admin']) || $user->hasRole('super_admin');
    }

    public function delete(User $user, Organization $organization): bool
    {
        $access = $user->organizations()->where('organizations.id', $organization->id)->first()?->pivot?->access_level;

        return $access === 'owner' || $user->hasRole('super_admin');
    }
}
