<?php

namespace App\Services\Portal;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * The staff-side services want "the user who did it". A patient acting online is not a staff user, so each organization gets one locked, login-less service user
 * (is_active false, random password) that stands in for the booker or cashier; the real actor is in the audit trail (account id) and the record's source column.
 */
class PortalSystemUser
{
    public function for(int $organizationId): User
    {
        $organization = Organization::query()->findOrFail($organizationId);
        $email = 'portal-service@'.Str::slug($organization->code ?: $organization->slug ?: (string) $organization->id).'.system.invalid';
        $user = User::query()->where('email', $email)->first();
        if ($user === null) {
            $user = User::query()->create(['name' => 'Patient portal (service)', 'email' => $email, 'password' => Str::random(48), 'is_active' => false]);
        }
        if (! $user->organizations()->where('organizations.id', $organizationId)->exists()) {
            $user->organizations()->attach($organizationId, ['access_level' => 'staff', 'is_default' => true]);
        }

        return $user;
    }

    public function branchFor(int $organizationId): ?Branch
    {
        return Branch::query()->where('organization_id', $organizationId)->orderBy('id')->first();
    }
}
