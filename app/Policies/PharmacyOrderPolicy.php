<?php

namespace App\Policies;

use App\Models\Pharmacy\PharmacyOrder;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PharmacyOrderPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('pharmacy.prescription.view');
    }

    public function view(User $user, PharmacyOrder $order): bool
    {
        return $user->can('pharmacy.prescription.view') && $this->inScope($user, $order);
    }

    public function review(User $user, PharmacyOrder $order): bool
    {
        return $user->can('pharmacy.prescription.review') && $this->inScope($user, $order);
    }

    public function cancel(User $user, PharmacyOrder $order): bool
    {
        return $user->can('pharmacy.prescription.review') && $this->inScope($user, $order);
    }

    private function inScope(User $user, PharmacyOrder $order): bool
    {
        return $user->companies()->where('companies.id', $order->company_id)->exists()
            && $user->branches()->where('branches.id', $order->branch_id)->exists();
    }
}
