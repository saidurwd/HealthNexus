<?php

namespace App\Support\Dashboard;

use Illuminate\Support\Facades\Route;

/** A link from a dashboard figure, given only when the person may open the page it goes to (menu hiding is not security, but a link to a page that answers 403 is a trap). */
class Link
{
    /** @param  array<string,mixed>  $parameters */
    public static function to(string $route, string $permission, array $parameters = []): ?string
    {
        $user = auth()->user();

        return $user && $user->can($permission) && Route::has($route) ? route($route, $parameters) : null;
    }
}
