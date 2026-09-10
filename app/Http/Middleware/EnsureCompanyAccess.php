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

        if ($companyId === null && $request->route('company')) {
            $companyId = $request->route('company')->id;
            $this->resolver->setCompanyId($companyId);
        }

        if ($companyId === null) {
            return response()->json(['error' => 'No company context set.'], 403);
        }

        $user = $request->user();

        $hasAccess = $user ? \DB::table('user_companies')
            ->where('user_id', $user->id)
            ->where('company_id', $companyId)
            ->exists() : false;

        if (! $hasAccess) {
            return response()->json(['error' => 'You do not have access to this company.'], 403);
        }

        return $next($request);
    }
}
