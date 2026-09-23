<?php

namespace App\Policies;

use App\Models\Pharmacy\PharmacySafetyAlert;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PharmacySafetyAlertPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('pharmacy.safety_alert.view');
    }

    public function view(User $user, PharmacySafetyAlert $alert): bool
    {
        return $user->can('pharmacy.safety_alert.view') && $this->inScope($user, $alert);
    }

    public function override(User $user, PharmacySafetyAlert $alert): bool
    {
        return $user->can('pharmacy.safety_alert.override') && $this->inScope($user, $alert);
    }

    private function inScope(User $user, PharmacySafetyAlert $alert): bool
    {
        return $user->companies()->where('companies.id', $alert->company_id)->exists();
    }
}
