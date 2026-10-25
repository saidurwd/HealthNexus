<?php

namespace App\Policies;

use App\Models\Ot\OtSurgery;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OtSurgeryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('ot.surgery.view');
    }

    public function view(User $user, OtSurgery $surgery): bool
    {
        return $user->can('ot.surgery.view') && $this->inScope($user, $surgery);
    }

    public function create(User $user): bool
    {
        return $user->can('ot.surgery.create');
    }

    public function update(User $user, OtSurgery $surgery): bool
    {
        return $user->can('ot.surgery.update') && $this->inScope($user, $surgery);
    }

    public function schedule(User $user, OtSurgery $surgery): bool
    {
        return $user->can('ot.schedule.create') && $this->inScope($user, $surgery);
    }

    public function cancel(User $user, OtSurgery $surgery): bool
    {
        return $user->can('ot.surgery.cancel') && $this->inScope($user, $surgery);
    }

    public function postpone(User $user, OtSurgery $surgery): bool
    {
        return $user->can('ot.surgery.postpone') && $this->inScope($user, $surgery);
    }

    public function start(User $user, OtSurgery $surgery): bool
    {
        return $user->can('ot.surgery.start') && $this->inScope($user, $surgery);
    }

    public function complete(User $user, OtSurgery $surgery): bool
    {
        return $user->can('ot.surgery.complete') && $this->inScope($user, $surgery);
    }

    public function manageTeam(User $user, OtSurgery $surgery): bool
    {
        return $user->can('ot.team.assign') && $this->inScope($user, $surgery);
    }

    private function inScope(User $user, OtSurgery $surgery): bool
    {
        return $user->companies()->where('companies.id', $surgery->company_id)->exists()
            && (! $surgery->branch_id || $user->branches()->where('branches.id', $surgery->branch_id)->exists());
    }
}
