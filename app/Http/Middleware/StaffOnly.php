<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** After `auth:sanctum` on staff API routes: a patient's mobile token is not a staff token, whatever the route. */
class StaffOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user() instanceof User, 403, __('This is not available to patient logins.'));

        return $next($request);
    }
}
