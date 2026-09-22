<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public function log(string $action, ?string $modelType = null, ?int $modelId = null, ?array $oldValues = null, ?array $newValues = null, ?Request $request = null): AuditLog
    {
        $request ??= request();

        $user = Auth::user();

        return AuditLog::create([
            'user_id' => $user?->id,
            'company_id' => app(TenantContextResolver::class)->getCompanyId(),
            'branch_id' => app(TenantContextResolver::class)->getBranchId(),
            'action' => $action,
            'module' => $this->moduleFor($modelType),
            'model_type' => $modelType,
            'model_id' => $modelId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'request_id' => app()->bound('request_id') ? app('request_id') : null,
        ]);
    }

    /**
     * Derives a coarse module label from the model's namespace (e.g. "App\Models\Billing\X" ->
     * "billing", "App\Models\Patient" -> "core") so audit records are filterable by area without
     * every call site having to pass one explicitly.
     */
    private function moduleFor(?string $modelType): ?string
    {
        if ($modelType === null) {
            return null;
        }

        $segments = explode('\\', $modelType);

        if (count($segments) >= 3 && $segments[0] === 'App' && $segments[1] === 'Models') {
            return count($segments) > 3 ? strtolower($segments[2]) : 'core';
        }

        return null;
    }
}
