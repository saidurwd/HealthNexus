<?php

namespace App\Policies;

use App\Models\Ot\OtRecoveryRecord;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OtRecoveryPolicy
{
    use HandlesAuthorization;

    public function view(User $user, OtRecoveryRecord $recovery): bool
    {
        return $user->can('ot.recovery.view') && $this->inScope($user, $recovery);
    }

    public function record(User $user, OtRecoveryRecord $recovery): bool
    {
        return $user->can('ot.recovery.record') && $this->inScope($user, $recovery);
    }

    public function complete(User $user, OtRecoveryRecord $recovery): bool
    {
        return $user->can('ot.recovery.complete') && $this->inScope($user, $recovery);
    }

    private function inScope(User $user, OtRecoveryRecord $recovery): bool
    {
        $surgery = $recovery->surgery;

        return $user->companies()->where('companies.id', $surgery->company_id)->exists()
            && (! $surgery->branch_id || $user->branches()->where('branches.id', $surgery->branch_id)->exists());
    }
}
