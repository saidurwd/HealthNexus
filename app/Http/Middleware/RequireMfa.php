<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireMfa
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->mfa_enabled && ! $request->session()->get('mfa_verified')) {
            return redirect()->route('mfa.challenge');
        }

        return $next($request);
    }
}
