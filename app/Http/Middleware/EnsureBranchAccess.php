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

        if ($companyId === null || $branchId === null) {
            abort(403, 'No company or branch context set.');
        }

        $user = $request->user();

        if ($user === null || ! $user->branches()->where('branches.id', $branchId)->exists()) {
            abort(403, 'You do not have access to this branch.');
        }

        return $next($request);
    }
}
