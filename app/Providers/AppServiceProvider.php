<?php

namespace App\Providers;

use App\Services\TenantContextResolver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantContextResolver::class, function ($app) {
            return new TenantContextResolver;
        });

        $this->app->singleton(\App\Services\Breadcrumbs::class, function ($app) {
            return new \App\Services\Breadcrumbs($app['request']);
        });
    }

    public function boot(): void
    {
        //
    }
}
