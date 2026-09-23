<?php

namespace App\Policies;

use App\Models\Pharmacy\PharmacyTransfer;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PharmacyTransferPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('pharmacy.stock.transfer');
    }

    public function view(User $user, PharmacyTransfer $transfer): bool
    {
        return $user->can('pharmacy.stock.transfer') && $this->inScope($user, $transfer);
    }

    public function create(User $user): bool
    {
        return $user->can('pharmacy.stock.transfer');
    }

    public function approve(User $user, PharmacyTransfer $transfer): bool
    {
        return $user->can('pharmacy.stock.transfer') && $this->inScope($user, $transfer);
    }

    public function dispatch(User $user, PharmacyTransfer $transfer): bool
    {
        return $user->can('pharmacy.stock.transfer') && $this->inScope($user, $transfer);
    }

    public function receive(User $user, PharmacyTransfer $transfer): bool
    {
        return $user->can('pharmacy.stock.transfer') && $this->inScope($user, $transfer);
    }

    private function inScope(User $user, PharmacyTransfer $transfer): bool
    {
        return $user->companies()->where('companies.id', $transfer->company_id)->exists();
    }
}
