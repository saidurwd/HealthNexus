<?php

namespace App\Http\Middleware;

use App\Services\TenantContextResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyAccess
{
    public function __construct(private TenantContextResolver $resolver) {}

    public function handle(Request $request, Closure $next): Response
    {
        $companyId = $this->resolver->getCompanyId();

        if ($companyId === null) {
            abort(403, 'No company context set.');
        }

        $user = $request->user();

        if ($user === null || ! $user->companies()->where('companies.id', $companyId)->exists()) {
            abort(403, 'You do not have access to this company.');
        }

        return $next($request);
    }
}
