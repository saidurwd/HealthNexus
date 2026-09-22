<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ActivityLogger
{
    public function __construct(protected TenantContextResolver $resolver) {}

    protected function requestId(): ?string
    {
        return app()->bound('request_id') ? app('request_id') : null;
    }

    public function log(
        string $action,
        ?string $description = null,
        ?Model $entity = null,
        array $properties = [],
        ?Request $request = null
    ): ActivityLog {
        $request ??= request();

        $user = Auth::user();
        $requestId = $this->requestId();

        $activity = ActivityLog::create([
            'user_id' => $user?->id,
            'company_id' => $this->resolver->getCompanyId(),
            'branch_id' => $this->resolver->getBranchId(),
            'action' => $action,
            'entity_type' => $entity ? $entity->getMorphClass() : null,
            'entity_id' => $entity ? $entity->getKey() : null,
            'description' => $description,
            'properties' => $properties,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'request_id' => $requestId,
        ]);

        Log::info('[Activity] '.$action, array_filter([
            'user_id' => $user?->id,
            'entity' => $entity ? [get_class($entity), $entity->getKey()] : null,
            'properties' => $properties,
            'request_id' => $requestId,
        ]));

        return $activity;
    }
}
