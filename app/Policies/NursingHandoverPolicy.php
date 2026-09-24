<?php

namespace App\Policies;

use App\Models\Nursing\NursingHandover;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NursingHandoverPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('nursing.handover.view');
    }

    public function view(User $user, NursingHandover $handover): bool
    {
        return $user->can('nursing.handover.view') && $this->inScope($user, $handover);
    }

    public function create(User $user): bool
    {
        return $user->can('nursing.handover.create');
    }

    public function acknowledge(User $user, NursingHandover $handover): bool
    {
        return $user->can('nursing.handover.acknowledge') && $this->inScope($user, $handover);
    }

    private function inScope(User $user, NursingHandover $handover): bool
    {
        return $user->companies()->where('companies.id', $handover->company_id)->exists()
            && (! $handover->branch_id || $user->branches()->where('branches.id', $handover->branch_id)->exists());
    }
}
