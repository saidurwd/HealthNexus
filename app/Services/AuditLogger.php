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
            'model_type' => $modelType,
            'model_id' => $modelId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
        ]);
    }
}
