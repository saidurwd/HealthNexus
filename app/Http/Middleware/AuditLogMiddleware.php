<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AuditLogMiddleware
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldLog($request, $response)) {
            $this->auditLogger->log(
                action: $this->determineAction($request),
                modelType: $this->determineModelType($request),
                modelId: $this->determineModelId($request, $response),
                oldValues: null,
                newValues: $this->shouldCapturePayload($request) ? $request->all() : null,
                request: $request
            );
        }

        return $response;
    }

    private function shouldLog(Request $request, SymfonyResponse $response): bool
    {
        if (! $request->user()) {
            return false;
        }

        if ($request->is('api/*') && $request->isMethod('get')) {
            return false;
        }

        if ($response->getStatusCode() >= 400) {
            return false;
        }

        return true;
    }

    private function determineAction(Request $request): string
    {
        return match ($request->method()) {
            'POST' => 'created',
            'PUT', 'PATCH' => 'updated',
            'DELETE' => 'deleted',
            default => 'accessed',
        };
    }

    private function determineModelType(Request $request): ?string
    {
        $path = $request->path();

        if (str_contains($path, 'companies')) {
            return Company::class;
        }

        if (str_contains($path, 'branches')) {
            return Branch::class;
        }

        if (str_contains($path, 'departments')) {
            return Department::class;
        }

        if (str_contains($path, 'users')) {
            return User::class;
        }

        return null;
    }

    private function determineModelId(Request $request, SymfonyResponse $response): ?int
    {
        $route = $request->route();

        if ($route) {
            $parameters = $route->parameters();

            if (isset($parameters['company']) && $parameters['company'] instanceof Company) {
                return $parameters['company']->id;
            }

            if (isset($parameters['branch']) && $parameters['branch'] instanceof Branch) {
                return $parameters['branch']->id;
            }

            if (isset($parameters['department']) && $parameters['department'] instanceof Department) {
                return $parameters['department']->id;
            }

            if (isset($parameters['user']) && $parameters['user'] instanceof User) {
                return $parameters['user']->id;
            }
        }

        return null;
    }

    private function shouldCapturePayload(Request $request): bool
    {
        return in_array($request->method(), ['POST', 'PUT', 'PATCH']);
    }
}
