<?php

namespace App\Policies;

use App\Models\Nursing\NursingEscalation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NursingEscalationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('nursing.escalation.view');
    }

    public function view(User $user, NursingEscalation $escalation): bool
    {
        return $user->can('nursing.escalation.view') && $this->inScope($user, $escalation);
    }

    public function create(User $user): bool
    {
        return $user->can('nursing.escalation.create');
    }

    public function acknowledge(User $user, NursingEscalation $escalation): bool
    {
        return $user->can('nursing.escalation.acknowledge') && $this->inScope($user, $escalation);
    }

    public function resolve(User $user, NursingEscalation $escalation): bool
    {
        return $user->can('nursing.escalation.resolve') && $this->inScope($user, $escalation);
    }

    private function inScope(User $user, NursingEscalation $escalation): bool
    {
        return $user->companies()->where('companies.id', $escalation->company_id)->exists()
            && (! $escalation->branch_id || $user->branches()->where('branches.id', $escalation->branch_id)->exists());
    }
}
