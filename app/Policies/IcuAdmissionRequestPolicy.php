<?php

namespace App\Policies;

use App\Models\Icu\IcuAdmissionRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class IcuAdmissionRequestPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('icu.admission_request.view');
    }

    public function view(User $user, IcuAdmissionRequest $request): bool
    {
        return $user->can('icu.admission_request.view') && $this->inScope($user, $request);
    }

    public function create(User $user): bool
    {
        return $user->can('icu.admission_request.create');
    }

    public function approve(User $user, IcuAdmissionRequest $request): bool
    {
        return $user->can('icu.admission_request.approve') && $this->inScope($user, $request);
    }

    public function reject(User $user, IcuAdmissionRequest $request): bool
    {
        return $user->can('icu.admission_request.reject') && $this->inScope($user, $request);
    }

    public function cancel(User $user, IcuAdmissionRequest $request): bool
    {
        return $user->can('icu.admission_request.cancel') && $this->inScope($user, $request);
    }

    private function inScope(User $user, IcuAdmissionRequest $request): bool
    {
        return $user->companies()->where('companies.id', $request->company_id)->exists()
            && (! $request->branch_id || $user->branches()->where('branches.id', $request->branch_id)->exists());
    }
}
