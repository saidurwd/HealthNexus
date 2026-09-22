<?php

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the active locale for the request: an explicit session override (set via
 * LocaleController::switch) takes priority, then the authenticated user's saved preference,
 * then the app default. Supported locales come from the localization.supported_locales setting
 * (see SettingsService) rather than being hardcoded here.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        // Locale resolution must never be able to take the whole app down — if the settings
        // table isn't migrated yet (fresh install) or the DB is briefly unavailable, fall back
        // to the app default rather than 500ing every single request.
        try {
            $supported = array_keys(app(SettingsService::class)->get('localization.supported_locales', []) ?: []);
        } catch (\Throwable) {
            $supported = [];
        }

        $locale = $request->session()->get('locale')
            ?? Auth::user()?->locale
            ?? config('app.locale');

        if (! empty($supported) && ! in_array($locale, $supported, true)) {
            $locale = config('app.locale');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
