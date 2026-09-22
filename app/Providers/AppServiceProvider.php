<?php

namespace App\Providers;

use App\Services\Breadcrumbs;
use App\Services\SettingsService;
use App\Services\TenantContextResolver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantContextResolver::class, function ($app) {
            return new TenantContextResolver;
        });

        $this->app->singleton(Breadcrumbs::class, function ($app) {
            return new Breadcrumbs($app['request']);
        });

        $this->app->singleton(SettingsService::class, function () {
            return new SettingsService;
        });
    }

    public function boot(): void
    {
        //
    }
}
