<?php

namespace App\Policies;

use App\Models\Ot\OtSurgery;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * No dedicated PatientConsent policy exists in the codebase — consent is gated via the
 * patients.consents.* permission directly. This thin wrapper checks the OT-specific
 * ot.consent.* permissions plus the surgery's own company/branch scope, since every surgical
 * consent action in this module is reached through an OtSurgery.
 */
class OtConsentPolicy
{
    use HandlesAuthorization;

    public function view(User $user, OtSurgery $surgery): bool
    {
        return $user->can('ot.consent.view') && $this->inScope($user, $surgery);
    }

    public function create(User $user, OtSurgery $surgery): bool
    {
        return $user->can('ot.consent.create') && $this->inScope($user, $surgery);
    }

    public function finalize(User $user, OtSurgery $surgery): bool
    {
        return $user->can('ot.consent.finalize') && $this->inScope($user, $surgery);
    }

    private function inScope(User $user, OtSurgery $surgery): bool
    {
        return $user->companies()->where('companies.id', $surgery->company_id)->exists()
            && (! $surgery->branch_id || $user->branches()->where('branches.id', $surgery->branch_id)->exists());
    }
}
