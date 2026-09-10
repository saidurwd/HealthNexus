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
    }

    public function boot(): void
    {
        //
    }
}
