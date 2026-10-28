<?php

namespace App\Http\Middleware;

use App\Models\PatientPortalAccount;
use App\Services\TenantContextResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Patient pages (guard `portal`) and the mobile API (Sanctum token with the ability `portal`). The organization is taken from the ACCOUNT, never from a header or the session
 * (a staff member may be signed in in the same browser). A staff token or a staff login is not a patient and is turned away.
 */
class PortalAuthenticate
{
    public function __construct(private readonly TenantContextResolver $tenant) {}

    public function handle(Request $request, Closure $next, string $mode = 'web'): Response
    {
        $account = $mode === 'api' ? $this->apiAccount($request) : Auth::guard('portal')->user();

        if (! $account instanceof PatientPortalAccount || $account->status !== 'active') {
            if ($mode === 'api') {
                return response()->json(['success' => false, 'message' => __('Please sign in.'), 'data' => null, 'meta' => []], 401);
            }
            Auth::guard('portal')->logout();

            return redirect()->route('portal.login');
        }

        // From here on `auth()->id()` and `Auth::user()` must mean "no staff member": the staff services record who did it, and a patient's id is not a user id.
        Auth::shouldUse('web');
        Auth::guard('web')->forgetUser();
        $this->tenant->setOrganizationId($account->organization_id);
        $request->attributes->set('portal_account', $account);
        // The app may ask for a language on any call (Accept-Language); otherwise the person's own choice stands.
        $asked = $mode === 'api' && $request->headers->has('Accept-Language') ? $request->getPreferredLanguage(['bn', 'en']) : null;
        App::setLocale($asked ?? ($account->language === 'en' ? 'en' : 'bn'));

        $response = $next($request);
        if ($mode === 'api') {
            $response->headers->set('X-Api-Version', (string) config('portal.api.version', 'v1'));
        } else {
            $response->headers->set('Cache-Control', 'no-store, private'); // health information must not sit in a shared computer's cache
        }

        return $response;
    }

    private function apiAccount(Request $request): ?PatientPortalAccount
    {
        $user = Auth::guard('sanctum')->user();

        return $user instanceof PatientPortalAccount && $user->tokenCan((string) config('portal.tokens.ability', 'portal')) ? $user : null;
    }
}
