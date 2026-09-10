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
        $companyId = $this->resolver->getCompanyId();
        $branchId = $this->resolver->getBranchId();

        if ($companyId === null && $request->route('company')) {
            $companyId = $request->route('company')->id;
            $this->resolver->setCompanyId($companyId);
        }

        if ($branchId === null && $request->route('branch')) {
            $branchId = $request->route('branch')->id;
            $this->resolver->setBranchId($branchId);
        }

        if ($companyId === null || $branchId === null) {
            return response()->json(['error' => 'No company or branch context set.'], 403);
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
