<?php

namespace App\Policies;

use App\Models\Encounter;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EncounterPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Encounter $encounter): bool
    {
        return $user->companies()->where('companies.id', $encounter->company_id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('encounters.create') || $user->hasRole('super_admin');
    }

    public function update(User $user, Encounter $encounter): bool
    {
        return $user->can('encounters.update') || $user->hasRole('super_admin');
    }

    public function delete(User $user, Encounter $encounter): bool
    {
        return $user->can('encounters.delete') || $user->hasRole('super_admin');
    }
}
