<?php

namespace App\Providers;

use App\Console\Commands\RefreshSystemStatistics;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Services\Dashboard\DashboardWidget;
use App\Services\Dashboard\DashboardWidgetRegistry;
use App\Services\SystemHealthService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the core (Phase 0) dashboard widgets. Other modules — Billing, and future ones —
 * should register their own widgets the same way, from their own service provider, rather than
 * this file growing to know about every module in the system.
 */
class DashboardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DashboardWidgetRegistry::class);
    }

    public function boot(): void
    {
        $registry = $this->app->make(DashboardWidgetRegistry::class);

        $registry->register(new DashboardWidget(
            key: 'total_users',
            title: 'Users',
            valueResolver: fn () => $this->cachedStat('total_users', fn () => User::count()),
            icon: 'bi bi-people',
            color: 'danger',
            routeName: 'admin.users.index',
            permission: 'manage users',
        ));

        $registry->register(new DashboardWidget(
            key: 'total_companies',
            title: 'Companies',
            valueResolver: fn () => $this->cachedStat('total_companies', fn () => Company::count()),
            icon: 'bi bi-building',
            color: 'info',
            routeName: 'admin.companies.index',
            permission: 'manage companies',
        ));

        $registry->register(new DashboardWidget(
            key: 'total_branches',
            title: 'Branches',
            valueResolver: fn () => $this->cachedStat('total_branches', fn () => Branch::count()),
            icon: 'bi bi-shop',
            color: 'success',
            // Branches are managed per-company (admin.companies.branches.index needs a
            // {company} route param), so this widget links to the companies list instead.
            routeName: 'admin.companies.index',
            permission: 'manage branches',
        ));

        $registry->register(new DashboardWidget(
            key: 'system_health',
            title: 'System Health',
            valueResolver: function () {
                $checks = $this->app->make(SystemHealthService::class)->check();
                $down = collect($checks)->where('status', SystemHealthService::DOWN)->count();

                return $down === 0 ? 'All OK' : "{$down} issue(s)";
            },
            icon: 'bi bi-heart-pulse',
            color: 'warning',
            routeName: 'admin.system.health',
            permission: 'system.health.view',
            refreshIntervalSeconds: 60,
        ));
    }

    private function cachedStat(string $key, \Closure $fallback): mixed
    {
        $stats = Cache::get(RefreshSystemStatistics::CACHE_KEY);

        return $stats[$key] ?? $fallback();
    }
}
