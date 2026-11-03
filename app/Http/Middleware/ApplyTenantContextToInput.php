<?php

namespace App\Http\Middleware;

use App\Services\TenantContextResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Entry forms never ask the user for an organization or branch. On every write request this stamps the
 * signed-in user's active tenant context (chosen at login) over any organization_id/branch_id in the
 * input — so controllers and Form Requests keep validating the same fields, but the values can
 * only ever come from the login context, never from the client.
 *
 * The context itself must be one the user actually belongs to; a mismatch (e.g. a forged
 * X-Organization-Id header on the API) is rejected rather than silently used.
 */
class ApplyTenantContextToInput
{
    private const EXCLUDED = ['login/context', 'api/v1/tenant/context'];

    public function __construct(private TenantContextResolver $resolver) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH'], true) || $this->isExcluded($request)) {
            return $next($request);
        }

        $user = $request->user();
        $organizationId = $this->resolver->getOrganizationId();
        $branchId = $this->resolver->getBranchId();

        if (! $user || ! $organizationId) {
            return $next($request);
        }

        if (! $user->organizations()->where('organizations.id', $organizationId)->exists()) {
            return response()->json(['error' => 'You do not have access to the active organization.'], 403);
        }

        $merge = ['organization_id' => $organizationId];

        if ($branchId) {
            $belongs = $user->branches()->where('branches.id', $branchId)->where('branches.organization_id', $organizationId)->exists();

            if (! $belongs) {
                return response()->json(['error' => 'You do not have access to the active branch.'], 403);
            }

            $merge['branch_id'] = $branchId;
        }

        $request->merge($merge);

        return $next($request);
    }

    private function isExcluded(Request $request): bool
    {
        foreach (self::EXCLUDED as $path) {
            if ($request->is($path)) {
                return true;
            }
        }

        return false;
    }
}
