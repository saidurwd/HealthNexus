<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        try {
            $companies = \App\Models\Company::where('is_active', true)->get();
        } catch (\Throwable $e) {
            $companies = collect();
        }

        return view('auth.login', compact('companies'));
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
        ]);

        $loginCredentials = [
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ];

        if (! Auth::attempt($loginCredentials, $request->filled('remember'))) {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        $user = Auth::user();

        $companyId = (int) $credentials['company_id'];
        $branchId = (int) $credentials['branch_id'];

        $hasCompanyAccess = $user->companies()->where('companies.id', $companyId)->exists();
        $hasBranchAccess = $user->branches()->where('branches.id', $branchId)->exists();

        if (! $hasCompanyAccess || ! $hasBranchAccess) {
            Auth::logout();

            return back()->withErrors([
                'email' => 'You do not have access to the selected company or branch.',
            ])->onlyInput('email');
        }

        $branch = \App\Models\Branch::findOrFail($branchId);

        if ($branch->company_id !== $companyId) {
            Auth::logout();

            return back()->withErrors([
                'email' => 'The selected branch does not belong to the selected company.',
            ])->onlyInput('email');
        }

        $request->session()->put('tenant_company_id', $companyId);
        $request->session()->put('tenant_branch_id', $branchId);

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
