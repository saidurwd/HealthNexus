<?php

namespace App\Policies;

use App\Models\Pharmacy\PharmacyBatch;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PharmacyBatchPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('pharmacy.batch.view');
    }

    public function view(User $user, PharmacyBatch $batch): bool
    {
        return $user->can('pharmacy.batch.view') && $this->inScope($user, $batch);
    }

    public function create(User $user): bool
    {
        return $user->can('pharmacy.batch.create');
    }

    public function update(User $user, PharmacyBatch $batch): bool
    {
        return $user->can('pharmacy.batch.update') && $this->inScope($user, $batch);
    }

    private function inScope(User $user, PharmacyBatch $batch): bool
    {
        return $user->companies()->where('companies.id', $batch->company_id)->exists()
            && (! $batch->branch_id || $user->branches()->where('branches.id', $batch->branch_id)->exists());
    }
}
