<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
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

        if (! Auth::attempt($credentials, $request->filled('remember'))) {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        $request->session()->put('login_email', $credentials['email']);

        return redirect()->route('login.context');
    }

    public function showCompanyBranchForm(Request $request)
    {
        $email = $request->session()->get('login_email');

        if (! $email) {
            return redirect()->route('login');
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return redirect()->route('login')->withErrors(['email' => 'User not found.']);
        }

        $companies = $user->companies()->get();

        return view('auth.context', compact('companies', 'user'));
    }

    public function storeCompanyBranch(Request $request)
    {
        $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
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

        $companyId = (int) $request->input('company_id');
        $branchId = (int) $request->input('branch_id');

        $hasCompanyAccess = $user->companies()->where('companies.id', $companyId)->exists();
        $hasBranchAccess = $user->branches()->where('branches.id', $branchId)->exists();

        if (! $hasCompanyAccess || ! $hasBranchAccess) {
            return back()->withErrors(['email' => 'You do not have access to the selected company or branch.']);
        }

        $branch = Branch::findOrFail($branchId);

        if ($branch->company_id !== $companyId) {
            return back()->withErrors(['email' => 'The selected branch does not belong to the selected company.']);
        }

        $request->session()->put('tenant_company_id', $companyId);
        $request->session()->put('tenant_branch_id', $branchId);
        $request->session()->forget('login_email');

        $user->update(['last_login_at' => now()]);

        return redirect()->intended('/dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
