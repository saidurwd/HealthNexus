<?php

namespace App\Http\Middleware;

use App\Services\TenantContextResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBranchAccess
{
    public function __construct(private TenantContextResolver $resolver) {}

    public function handle(Request $request, Closure $next): Response
    {
        $organizationId = $this->resolver->getOrganizationId();
        $branchId = $this->resolver->getBranchId();

        if ($organizationId === null && $request->route('organization')) {
            $organizationId = $request->route('organization')->id;
            $this->resolver->setOrganizationId($organizationId);
        }

        if ($branchId === null && $request->route('branch')) {
            $branchId = $request->route('branch')->id;
            $this->resolver->setBranchId($branchId);
        }

        if ($organizationId === null || $branchId === null) {
            return response()->json(['error' => 'No organization or branch context set.'], 403);
        }

        $user = $request->user();

        $hasAccess = $user ? \DB::table('user_branches')
            ->where('user_id', $user->id)
            ->where('branch_id', $branchId)
            ->exists() : false;

        if (! $hasAccess) {
            return response()->json([
                'error' => 'You do not have access to this branch.',
                'debug' => [
                    'user_id' => $user?->id,
                    'branch_id' => $branchId,
                    'user_branches_count' => $user ? \DB::table('user_branches')->where('user_id', $user->id)->count() : 0,
                ],
            ], 403);
        }

        return $next($request);
    }
}
