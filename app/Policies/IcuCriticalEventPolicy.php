<?php

namespace App\Policies;

use App\Models\Icu\IcuCriticalEvent;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class IcuCriticalEventPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('icu.critical_event.view');
    }

    public function view(User $user, IcuCriticalEvent $event): bool
    {
        return $user->can('icu.critical_event.view') && $this->inScope($user, $event);
    }

    public function create(User $user): bool
    {
        return $user->can('icu.critical_event.create');
    }

    public function finalize(User $user, IcuCriticalEvent $event): bool
    {
        return $user->can('icu.critical_event.finalize') && $this->inScope($user, $event);
    }

    private function inScope(User $user, IcuCriticalEvent $event): bool
    {
        return $user->organizations()->where('organizations.id', $event->organization_id)->exists()
            && (! $event->branch_id || $user->branches()->where('branches.id', $event->branch_id)->exists());
    }
}
