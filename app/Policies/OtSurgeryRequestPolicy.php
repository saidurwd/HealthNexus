<?php

namespace App\Policies;

use App\Models\Ot\OtSurgeryRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OtSurgeryRequestPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('ot.surgery.view');
    }

    public function view(User $user, OtSurgeryRequest $request): bool
    {
        return $user->can('ot.surgery.view') && $this->inScope($user, $request);
    }

    public function create(User $user): bool
    {
        return $user->can('ot.surgery.create');
    }

    public function approve(User $user, OtSurgeryRequest $request): bool
    {
        return $user->can('ot.surgery.approve') && $this->inScope($user, $request);
    }

    public function cancel(User $user, OtSurgeryRequest $request): bool
    {
        return $user->can('ot.surgery.cancel') && $this->inScope($user, $request);
    }

    private function inScope(User $user, OtSurgeryRequest $request): bool
    {
        return $user->organizations()->where('organizations.id', $request->organization_id)->exists()
            && (! $request->branch_id || $user->branches()->where('branches.id', $request->branch_id)->exists());
    }
}
