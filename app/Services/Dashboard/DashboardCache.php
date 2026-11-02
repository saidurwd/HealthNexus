<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Support\Dashboard\DashboardPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * A short cache for what a dashboard works out, so a ward-wall screen does not scan the hospital every few seconds. The key holds the hospital, the branch, the period and the viewer's rights, so a figure computed for one
 * scope is never shown to another. The time it was worked out is kept and shown as "as of".
 */
class DashboardCache
{
    /**
     * @template T
     *
     * @param  \Closure():T  $compute
     * @return array{data:T,as_of:CarbonImmutable}
     */
    public function remember(User $user, string $dashboard, ?int $organizationId, ?int $branchId, ?DashboardPeriod $period, \Closure $compute, ?int $seconds = null, bool $fresh = false): array
    {
        $key = $this->key($user, $dashboard, $organizationId, $branchId, $period);
        $seconds ??= (int) config('ux.dashboard.cache_seconds', 60);
        if ($fresh || $seconds <= 0) {
            Cache::forget($key);
        }
        if ($seconds <= 0) {
            return ['data' => $compute(), 'as_of' => CarbonImmutable::now()->startOfSecond()];
        }
        $hit = Cache::get($key);
        if (is_array($hit) && isset($hit['as_of'])) {
            return ['data' => $hit['data'], 'as_of' => CarbonImmutable::parse($hit['as_of'])];
        }
        $out = ['data' => $compute(), 'as_of' => CarbonImmutable::now()->startOfSecond()];
        Cache::put($key, ['data' => $out['data'], 'as_of' => $out['as_of']->toIso8601String()], $seconds);

        return $out;
    }

    public function key(User $user, string $dashboard, ?int $organizationId, ?int $branchId, ?DashboardPeriod $period): string
    {
        $rights = $user->getAllPermissions()->pluck('name')->sort()->values()->all();

        return 'dash:'.$dashboard.':o'.($organizationId ?? 0).':b'.($branchId ?? 0).':'.($period ? $period->key.'-'.$period->fromDate().'-'.$period->toDate() : 'live').':'.substr(sha1(json_encode($rights)), 0, 12).($user->hasRole('super_admin') ? ':sa' : '');
    }
}
