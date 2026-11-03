<?php

namespace App\Services\Ux;

use App\Models\User;
use App\Services\ModuleManager;
use Illuminate\Support\Facades\Route;

/** Where a user lands after login: their own worklist, or the dashboard when none applies (guideline 19.2 rule 2). */
class RoleHome
{
    public function __construct(private readonly ModuleManager $modules) {}

    public function urlFor(User $user): string
    {
        $homes = config('ux.role_homes');

        foreach ($user->getRoleNames() as $role) {
            $home = $homes[$role] ?? null;

            if ($home === null || ! Route::has($home['route'])) {
                continue;
            }
            if (isset($home['module']) && ! $this->modules->isEnabled($home['module'])) {
                continue;
            }
            if (isset($home['permission']) && ! $user->can($home['permission'])) {
                continue;
            }

            return route($home['route']);
        }

        return route('home');
    }
}
