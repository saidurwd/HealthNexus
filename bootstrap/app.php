<?php

use App\Http\Middleware\RequestIdMiddleware;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetTenantContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // Every listener is registered explicitly in AppServiceProvider::boot(); auto-discovery would
    // register each handle*() method a second time and fire every listener twice.
    ->withEvents(discover: false)
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [
            RequestIdMiddleware::class,
        ]);

        // SetLocale needs session access (session/user-saved locale), so it must run after
        // Laravel's own StartSession middleware — which only exists inside the default 'web'
        // group, not before it. append: runs after the whole group, including StartSession.
        $middleware->web(append: [
            SetLocale::class,
            SetTenantContext::class,
        ]);

        $middleware->api(prepend: [
            RequestIdMiddleware::class,
            SetTenantContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
