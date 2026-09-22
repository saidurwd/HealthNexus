<?php

namespace App\Services\Dashboard;

/**
 * A single dashboard stat tile. The value is a closure rather than a plain scalar so it's only
 * ever evaluated for users who are actually permitted to see it (DashboardWidgetRegistry::forUser
 * filters by permission before resolving any value) — a widget's data provider should never run
 * just to be hidden afterward.
 */
final class DashboardWidget
{
    public function __construct(
        public readonly string $key,
        public readonly string $title,
        public readonly \Closure $valueResolver,
        public readonly string $icon = 'bi bi-graph-up',
        public readonly string $color = 'primary',
        public readonly ?string $routeName = null,
        public readonly ?string $permission = null,
        public readonly int $refreshIntervalSeconds = 300,
    ) {}

    public function resolveValue(): mixed
    {
        return ($this->valueResolver)();
    }

    public function url(): ?string
    {
        if ($this->routeName === null) {
            return null;
        }

        return \Illuminate\Support\Facades\Route::has($this->routeName) ? route($this->routeName) : null;
    }
}
