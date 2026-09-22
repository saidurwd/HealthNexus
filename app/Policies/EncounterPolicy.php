<?php

namespace App\Policies;

use App\Models\BreakGlassAccess;
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
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($this->userHasActiveBreakGlass($user, $encounter)) {
            return true;
        }

        if ($encounter->locked_at) {
            return false;
        }

        return $user->can('encounters.update');
    }

    public function delete(User $user, Encounter $encounter): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($this->userHasActiveBreakGlass($user, $encounter)) {
            return true;
        }

        return $user->can('encounters.delete') && ! $encounter->locked_at;
    }

    private function userHasActiveBreakGlass(User $user, Encounter $encounter): bool
    {
        return BreakGlassAccess::where('user_id', $user->id)
            ->where('encounter_id', $encounter->id)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->exists();
    }
}
