<?php

namespace App\Policies;

use App\Models\Radiology\RadiologyCriticalFinding;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RadiologyCriticalFindingPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('radiology.critical_finding.view');
    }

    public function view(User $user, RadiologyCriticalFinding $finding): bool
    {
        return $user->can('radiology.critical_finding.view') && $this->inScope($user, $finding);
    }

    public function acknowledge(User $user, RadiologyCriticalFinding $finding): bool
    {
        return $user->can('radiology.critical_finding.acknowledge') && $this->inScope($user, $finding);
    }

    private function inScope(User $user, RadiologyCriticalFinding $finding): bool
    {
        return $user->companies()->where('companies.id', $finding->company_id)->exists()
            && $user->branches()->where('branches.id', $finding->branch_id)->exists();
    }
}
