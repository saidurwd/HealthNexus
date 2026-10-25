<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\LoginHistory;
use App\Models\User;
use App\Services\SecurityLogger;
use App\Services\Ux\RoleHome;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function __construct(private SecurityLogger $securityLogger) {}

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if ($user && $user->isLocked()) {
            $this->recordLoginAttempt($request, $user, 'failed', 'account_locked');
            $this->securityLogger->log('LOGIN_BLOCKED_LOCKED', ['email' => $credentials['email']], SecurityLogger::WARNING, $user, $request);

            return back()->withErrors([
                'email' => 'This account is temporarily locked due to too many failed login attempts. Try again later.',
            ])->onlyInput('email');
        }

        if ($user && ! $user->is_active) {
            $this->recordLoginAttempt($request, $user, 'failed', 'inactive');

            return back()->withErrors([
                'email' => 'This account is inactive. Contact your administrator.',
            ])->onlyInput('email');
        }

        if (! Auth::attempt($credentials, $request->filled('remember'))) {
            if ($user) {
                $this->registerFailedAttempt($request, $user);
            }

            $this->recordLoginAttempt($request, $user, 'failed', 'invalid_credentials');

            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        $user->update(['failed_login_attempts' => 0, 'locked_until' => null]);

        $history = $this->recordLoginAttempt($request, $user, 'success', null, now());
        $request->session()->put('login_history_id', $history->id);

        $request->session()->put('login_email', $credentials['email']);

        return redirect()->route('login.context');
    }

    private function registerFailedAttempt(Request $request, User $user): void
    {
        $attempts = $user->failed_login_attempts + 1;

        if ($attempts >= User::MAX_FAILED_LOGIN_ATTEMPTS) {
            $user->update([
                'failed_login_attempts' => $attempts,
                'locked_until' => now()->addMinutes(User::LOCKOUT_MINUTES),
            ]);

            $this->securityLogger->log('ACCOUNT_LOCKED', ['attempts' => $attempts], SecurityLogger::CRITICAL, $user, $request);

            return;
        }

        $user->update(['failed_login_attempts' => $attempts]);

        $this->securityLogger->log('LOGIN_FAILED', ['attempts' => $attempts], SecurityLogger::WARNING, $user, $request);
    }

    private function recordLoginAttempt(Request $request, ?User $user, string $status, ?string $failureReason, ?Carbon $loggedInAt = null): LoginHistory
    {
        return LoginHistory::create([
            'user_id' => $user?->id,
            'email' => $request->input('email'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => $status,
            'failure_reason' => $failureReason,
            'logged_in_at' => $loggedInAt,
        ]);
    }

    public function showOrganizationBranchForm(Request $request)
    {
        $email = $request->session()->get('login_email');

        if (! $email) {
            return redirect()->route('login');
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return redirect()->route('login')->withErrors(['email' => 'User not found.']);
        }

        $organizations = $user->organizations()->get();

        return view('auth.context', compact('organizations', 'user'));
    }

    public function storeOrganizationBranch(Request $request)
    {
        $request->validate([
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
        ]);

        $email = $request->session()->get('login_email');

        if (! $email) {
            return redirect()->route('login')->withErrors(['email' => 'Session expired. Please login again.']);
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return redirect()->route('login')->withErrors(['email' => 'User not found.']);
        }

        $organizationId = (int) $request->input('organization_id');
        $branchId = (int) $request->input('branch_id');

        $hasOrganizationAccess = $user->organizations()->where('organizations.id', $organizationId)->exists();
        $hasBranchAccess = $user->branches()->where('branches.id', $branchId)->exists();

        if (! $hasOrganizationAccess || ! $hasBranchAccess) {
            return back()->withErrors(['email' => 'You do not have access to the selected organization or branch.']);
        }

        $branch = Branch::findOrFail($branchId);

        if ($branch->organization_id !== $organizationId) {
            return back()->withErrors(['email' => 'The selected branch does not belong to the selected organization.']);
        }

        $request->session()->put('tenant_organization_id', $organizationId);
        $request->session()->put('tenant_branch_id', $branchId);
        $request->session()->forget('login_email');

        $user->update(['last_login_at' => now()]);

        return redirect()->intended(app(RoleHome::class)->urlFor($user));
    }

    public function logout(Request $request)
    {
        if ($historyId = $request->session()->get('login_history_id')) {
            LoginHistory::whereKey($historyId)->update(['logged_out_at' => now()]);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
