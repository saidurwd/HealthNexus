<?php

namespace App\Policies;

use App\Models\Currency;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CurrencyPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Currency $currency): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('settings.update');
    }

    public function update(User $user, Currency $currency): bool
    {
        return $user->hasPermissionTo('settings.update');
    }

    public function delete(User $user, Currency $currency): bool
    {
        return $user->hasRole('super_admin');
    }
}
