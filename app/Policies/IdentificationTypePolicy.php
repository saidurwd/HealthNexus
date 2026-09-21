<?php

namespace App\Policies;

use App\Models\IdentificationType;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class IdentificationTypePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, IdentificationType $identificationType): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('settings.update');
    }

    public function update(User $user, IdentificationType $identificationType): bool
    {
        return $user->hasPermissionTo('settings.update');
    }

    public function delete(User $user, IdentificationType $identificationType): bool
    {
        return $user->hasRole('super_admin');
    }
}
