<?php

namespace App\Policies;

use App\Models\Icu\IcuCriticalAlert;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class IcuAlertPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('icu.alert.view');
    }

    public function view(User $user, IcuCriticalAlert $alert): bool
    {
        return $user->can('icu.alert.view') && $this->inScope($user, $alert);
    }

    public function acknowledge(User $user, IcuCriticalAlert $alert): bool
    {
        return $user->can('icu.alert.acknowledge') && $this->inScope($user, $alert);
    }

    public function manage(User $user, IcuCriticalAlert $alert): bool
    {
        return $user->can('icu.alert.manage') && $this->inScope($user, $alert);
    }

    private function inScope(User $user, IcuCriticalAlert $alert): bool
    {
        return $user->organizations()->where('organizations.id', $alert->organization_id)->exists()
            && (! $alert->branch_id || $user->branches()->where('branches.id', $alert->branch_id)->exists());
    }
}
