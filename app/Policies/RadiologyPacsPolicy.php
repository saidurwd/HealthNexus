<?php

namespace App\Policies;

use App\Models\Radiology\RadiologyPacsServer;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RadiologyPacsPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('radiology.pacs.view');
    }

    public function view(User $user, RadiologyPacsServer $server): bool
    {
        return $user->can('radiology.pacs.view') && $this->inScope($user, $server);
    }

    public function manage(User $user, ?RadiologyPacsServer $server = null): bool
    {
        return $user->can('radiology.pacs.manage') && (! $server || $this->inScope($user, $server));
    }

    private function inScope(User $user, RadiologyPacsServer $server): bool
    {
        return $user->companies()->where('companies.id', $server->company_id)->exists();
    }
}
