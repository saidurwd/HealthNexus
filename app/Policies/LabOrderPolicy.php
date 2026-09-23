<?php

namespace App\Policies;

use App\Models\Laboratory\LabOrder;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LabOrderPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('lab.order.view');
    }

    public function view(User $user, LabOrder $order): bool
    {
        return $user->can('lab.order.view') && $this->inScope($user, $order);
    }

    public function create(User $user): bool
    {
        return $user->can('lab.order.create');
    }

    public function cancel(User $user, LabOrder $order): bool
    {
        return $user->can('lab.order.cancel') && $this->inScope($user, $order);
    }

    private function inScope(User $user, LabOrder $order): bool
    {
        return $user->companies()->where('companies.id', $order->company_id)->exists()
            && $user->branches()->where('branches.id', $order->branch_id)->exists();
    }
}
