<?php

namespace Modules\Portal\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Portal\PortalDevice;
use App\Services\Portal\PortalAuthService;
use App\Services\Portal\PortalException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/** Patient sign-up, sign-in and password reset pages (S01, S02). Light pages; no script needed. */
class PortalWebAuthController extends Controller
{
    public function __construct(private readonly PortalAuthService $auth) {}

    private function device(Request $request): string
    {
        return (string) ($request->cookie('portal_device') ?: Str::uuid());
    }

    private function signIn(Request $request, $account, string $uid)
    {
        $this->auth->registerDevice($account, $uid, mb_substr((string) $request->userAgent(), 0, 90), 'web', false);
        Auth::guard('portal')->login($account);
        $request->session()->regenerate();
        $request->session()->put('locale', $account->language);

        return redirect()->route('portal.home')->withCookie(Cookie::make('portal_device', $uid, 60 * 24 * 400, '/', null, $request->isSecure(), true, false, 'lax'));
    }

    public function showLogin(Request $request)
    {
        return Auth::guard('portal')->check() ? redirect()->route('portal.home') : view('portal.login');
    }

    public function login(Request $request)
    {
        $d = $request->validate(['phone' => ['required', 'string', 'max:25'], 'password' => ['required', 'string', 'max:100']]);
        $uid = $this->device($request);
        $r = $this->auth->attempt($d['phone'], $d['password'], $uid);
        if ($r['otp_required']) {
            return redirect()->route('portal.login.code')->with('challenge', $r['challenge'])->withCookie(Cookie::make('portal_device', $uid, 60 * 24 * 400, '/', null, $request->isSecure(), true, false, 'lax'));
        }

        return $this->signIn($request, $r['account'], $uid);
    }

    public function showCode(Request $request)
    {
        $request->session()->reflash(); // a refresh must not lose the step the person is on

        return $request->session()->has('challenge') ? view('portal.code', ['challenge' => $request->session()->get('challenge'), 'action' => route('portal.login.code.verify'), 'title' => __('Check your phone')]) : redirect()->route('portal.login');
    }

    public function verifyCode(Request $request)
    {
        $d = $request->validate(['challenge' => ['required', 'string'], 'code' => ['required', 'string', 'max:10']]);
        try {
            $account = $this->auth->completeLoginOtp($d['challenge'], $d['code']);
        } catch (PortalException $e) {
            return back()->with('challenge', $d['challenge'])->with('error', $e->getMessage());
        }

        return $this->signIn($request, $account, $this->device($request));
    }

    public function showRegister()
    {
        return view('portal.register');
    }

    public function register(Request $request)
    {
        $d = $request->validate(['hospital' => ['nullable', 'string', 'max:60'], 'identifier' => ['required', 'string', 'max:60'], 'date_of_birth' => ['required', 'date'], 'phone' => ['required', 'string', 'max:25']]);
        $r = $this->auth->startRegistration($d);

        return redirect()->route('portal.register.verify')->with('challenge', $r['challenge'])->with('success', __('If the details match our records, a code has been sent to the phone number we have for you.'));
    }

    public function showVerify(Request $request)
    {
        $request->session()->reflash();

        return $request->session()->has('challenge') ? view('portal.verify', ['challenge' => $request->session()->get('challenge')]) : redirect()->route('portal.register');
    }

    public function verify(Request $request)
    {
        $d = $request->validate(['challenge' => ['required', 'string'], 'code' => ['required', 'string', 'max:10'], 'password' => ['required', 'string', 'max:100', 'confirmed'], 'accept_terms' => ['accepted']]);
        try {
            $account = $this->auth->completeRegistration($d['challenge'], $d['code'], $d['password'], true);
        } catch (PortalException $e) {
            return back()->with('challenge', $d['challenge'])->with('error', $e->getMessage());
        }

        return $this->signIn($request, $account, $this->device($request));
    }

    public function showActivate()
    {
        return view('portal.activate');
    }

    public function activate(Request $request)
    {
        $d = $request->validate(['code' => ['required', 'string', 'max:20'], 'date_of_birth' => ['required', 'date'], 'password' => ['required', 'string', 'max:100', 'confirmed'], 'accept_terms' => ['accepted']]);
        $account = $this->auth->claimInvitation($d['code'], $d['date_of_birth'], $d['password'], true);

        return $this->signIn($request, $account, $this->device($request));
    }

    public function showForgot()
    {
        return view('portal.forgot');
    }

    public function forgot(Request $request)
    {
        $d = $request->validate(['phone' => ['required', 'string', 'max:25']]);
        $r = $this->auth->startReset($d['phone']);

        return redirect()->route('portal.reset')->with('challenge', $r['challenge'])->with('success', __('If this number has a login, a code has been sent to it.'));
    }

    public function showReset(Request $request)
    {
        $request->session()->reflash();

        return $request->session()->has('challenge') ? view('portal.reset', ['challenge' => $request->session()->get('challenge')]) : redirect()->route('portal.forgot');
    }

    public function reset(Request $request)
    {
        $d = $request->validate(['challenge' => ['required', 'string'], 'code' => ['required', 'string', 'max:10'], 'password' => ['required', 'string', 'max:100', 'confirmed']]);
        try {
            $this->auth->completeReset($d['challenge'], $d['code'], $d['password']);
        } catch (PortalException $e) {
            return back()->with('challenge', $d['challenge'])->with('error', $e->getMessage());
        }

        return redirect()->route('portal.login')->with('success', __('Your password was changed. Please sign in again.'));
    }

    public function logout(Request $request)
    {
        $account = Auth::guard('portal')->user();
        if ($account) {
            PortalDevice::query()->where(['account_id' => $account->id, 'device_hash' => hash('sha256', $this->device($request))])->update(['trusted_at' => $request->boolean('forget') ? null : now()]);
        }
        Auth::guard('portal')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }

    public function language(Request $request, string $lang)
    {
        abort_unless(in_array($lang, ['bn', 'en'], true), 404);
        $request->session()->put('locale', $lang);
        if ($account = Auth::guard('portal')->user()) {
            $account->update(['language' => $lang]);
        }

        return back();
    }
}
