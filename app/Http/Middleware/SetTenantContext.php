<?php

namespace App\Http\Middleware;

use App\Services\TenantContextResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    public function __construct(private TenantContextResolver $resolver) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->resolver->resolveFromRequest($request);

        return $next($request);
    }

    public function terminate(Request $request, ?Response $response): void
    {
        $this->resolver->clear();
    }
}
