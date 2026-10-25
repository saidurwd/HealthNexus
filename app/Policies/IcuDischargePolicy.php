<?php

namespace App\Policies;

use App\Models\Icu\IcuDischargeSummary;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class IcuDischargePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('icu.discharge.view');
    }

    public function view(User $user, IcuDischargeSummary $summary): bool
    {
        return $user->can('icu.discharge.view') && $this->inScope($user, $summary);
    }

    public function create(User $user): bool
    {
        return $user->can('icu.discharge.create');
    }

    public function finalize(User $user, IcuDischargeSummary $summary): bool
    {
        return $user->can('icu.discharge.finalize') && $this->inScope($user, $summary);
    }

    private function inScope(User $user, IcuDischargeSummary $summary): bool
    {
        return $user->organizations()->where('organizations.id', $summary->organization_id)->exists()
            && (! $summary->branch_id || $user->branches()->where('branches.id', $summary->branch_id)->exists());
    }
}
