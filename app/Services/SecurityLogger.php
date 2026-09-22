<?php

namespace App\Services;

use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SecurityLogger
{
    public const INFO = 'info';

    public const WARNING = 'warning';

    public const CRITICAL = 'critical';

    public function __construct(protected TenantContextResolver $resolver) {}

    public function log(
        string $event,
        array $properties = [],
        string $severity = self::INFO,
        ?User $user = null,
        ?Request $request = null
    ): SecurityEvent {
        $request ??= request();
        $user ??= Auth::user();
        $requestId = app()->bound('request_id') ? app('request_id') : null;

        $securityEvent = SecurityEvent::create([
            'user_id' => $user?->id,
            'company_id' => $this->resolver->getCompanyId(),
            'branch_id' => $this->resolver->getBranchId(),
            'event' => $event,
            'severity' => $severity,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'request_id' => $requestId,
            'properties' => $properties,
        ]);

        $level = $severity === self::CRITICAL ? 'critical' : 'info';

        Log::channel('stack')->{$level}(
            '[Security] '.$event,
            array_filter([
                'user_id' => $user?->id,
                'properties' => $properties,
                'request_id' => $requestId,
            ])
        );

        return $securityEvent;
    }
}
