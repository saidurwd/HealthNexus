<?php

namespace App\Services\Dashboard;

use App\Models\User;

/**
 * Central place any module registers dashboard widgets against, so the dashboard itself never
 * needs to know which modules exist. Register widgets from a service provider's boot() (see
 * AppServiceProvider for the core set); Billing or future modules can inject this registry and
 * add their own without touching HomeController or the dashboard view.
 */
class DashboardWidgetRegistry
{
    /** @var array<string, DashboardWidget> */
    private array $widgets = [];

    public function register(DashboardWidget $widget): void
    {
        $this->widgets[$widget->key] = $widget;
    }

    /**
     * @return array<int, DashboardWidget>
     */
    public function all(): array
    {
        return array_values($this->widgets);
    }

    /**
     * Widgets visible to the given user, permission-filtered before any value is resolved.
     *
     * @return array<int, DashboardWidget>
     */
    public function forUser(User $user): array
    {
        return array_values(array_filter(
            $this->widgets,
            fn (DashboardWidget $widget) => $widget->permission === null
                || $user->can($widget->permission)
                || $user->hasRole('super_admin'),
        ));
    }
}
