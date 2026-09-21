<?php

namespace App\Policies;

use App\Models\State;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class StatePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, State $state): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('settings.update');
    }

    public function update(User $user, State $state): bool
    {
        return $user->hasPermissionTo('settings.update');
    }

    public function delete(User $user, State $state): bool
    {
        return $user->hasRole('super_admin');
    }
}
