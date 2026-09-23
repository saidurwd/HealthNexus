<?php

namespace App\Policies;

use App\Models\Radiology\RadiologyStudy;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RadiologyStudyPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('radiology.study.view');
    }

    public function view(User $user, RadiologyStudy $study): bool
    {
        return $user->can('radiology.study.view') && $this->inScope($user, $study);
    }

    public function export(User $user, RadiologyStudy $study): bool
    {
        return $user->can('radiology.study.export') && $this->inScope($user, $study);
    }

    private function inScope(User $user, RadiologyStudy $study): bool
    {
        return $user->companies()->where('companies.id', $study->company_id)->exists()
            && $user->branches()->where('branches.id', $study->branch_id)->exists();
    }
}
