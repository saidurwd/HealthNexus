<?php

namespace App\Http\Middleware;

use App\Services\TenantContextResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationAccess
{
    public function __construct(private TenantContextResolver $resolver) {}

    public function handle(Request $request, Closure $next): Response
    {
        $organizationId = $this->resolver->getOrganizationId();

        if ($organizationId === null && $request->route('organization')) {
            $organizationId = $request->route('organization')->id;
            $this->resolver->setOrganizationId($organizationId);
        }

        if ($organizationId === null) {
            return response()->json(['error' => 'No organization context set.'], 403);
        }

        $user = $request->user();

        $hasAccess = $user ? \DB::table('user_organizations')
            ->where('user_id', $user->id)
            ->where('organization_id', $organizationId)
            ->exists() : false;

        if (! $hasAccess) {
            return response()->json(['error' => 'You do not have access to this organization.'], 403);
        }

        return $next($request);
    }
}
