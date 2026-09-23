<?php

namespace App\Policies;

use App\Models\Radiology\RadiologyOrder;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RadiologyOrderPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('radiology.order.view');
    }

    public function view(User $user, RadiologyOrder $order): bool
    {
        return $user->can('radiology.order.view') && $this->inScope($user, $order);
    }

    public function create(User $user): bool
    {
        return $user->can('radiology.order.create');
    }

    public function cancel(User $user, RadiologyOrder $order): bool
    {
        return $user->can('radiology.order.cancel') && $this->inScope($user, $order);
    }

    private function inScope(User $user, RadiologyOrder $order): bool
    {
        return $user->companies()->where('companies.id', $order->company_id)->exists()
            && $user->branches()->where('branches.id', $order->branch_id)->exists();
    }
}
