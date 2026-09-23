<?php

namespace App\Policies;

use App\Models\Radiology\RadiologyExamination;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RadiologyExaminationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('radiology.examination.view');
    }

    public function view(User $user, RadiologyExamination $examination): bool
    {
        return $user->can('radiology.examination.view') && $this->inScope($user, $examination);
    }

    public function start(User $user, RadiologyExamination $examination): bool
    {
        return $user->can('radiology.examination.start') && $this->inScope($user, $examination);
    }

    public function complete(User $user, RadiologyExamination $examination): bool
    {
        return $user->can('radiology.examination.complete') && $this->inScope($user, $examination);
    }

    private function inScope(User $user, RadiologyExamination $examination): bool
    {
        return $user->companies()->where('companies.id', $examination->company_id)->exists()
            && $user->branches()->where('branches.id', $examination->branch_id)->exists();
    }
}
