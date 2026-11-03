<?php

namespace App\Http\Middleware;

use App\Services\TenantContextResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantContext
{
    public function __construct(private TenantContextResolver $resolver) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $organizationId = $this->resolver->getOrganizationId();
            $branchId = $this->resolver->getBranchId();

            if (! $organizationId || ! $branchId) {
                return redirect()->route('login')->withErrors([
                    'email' => 'Please select an organization and branch to continue.',
                ]);
            }
        }

        return $next($request);
    }
}
