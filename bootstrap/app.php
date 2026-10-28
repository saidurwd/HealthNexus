<?php

use App\Http\Middleware\EnsureModuleEnabled;
use App\Http\Middleware\PortalAuthenticate;
use App\Http\Middleware\PortalEtag;
use App\Http\Middleware\RequestIdMiddleware;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetTenantContext;
use App\Http\Middleware\StaffOnly;
use App\Services\Portal\PortalException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
        // Global, so even a 404 (no route matched, no web group) carries a request id for the error screen.
        $middleware->prepend(RequestIdMiddleware::class);
        $middleware->append(SecurityHeaders::class);

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

        $middleware->alias([
            'module' => EnsureModuleEnabled::class,
            'portal.auth' => PortalAuthenticate::class,
            'portal.etag' => PortalEtag::class,
            'staff.only' => StaffOnly::class,
        ]);

        $middleware->api(prepend: [
            RequestIdMiddleware::class,
            SetTenantContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // A refusal meant for the patient (Phase 17): the same envelope on the API, a flash message on the pages.
        $exceptions->render(function (PortalException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage(), 'data' => null, 'meta' => $e->extra], $e->status);
            }

            if ($e->status === 403) {
                return response()->view('errors.403', ['exception' => new HttpException(403, $e->getMessage())], 403);
            }

            return back()->with('error', $e->getMessage())->withInput($request->except(['password', 'code']));
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
