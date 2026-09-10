<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->when($request->filled('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"))
            ->latest()
            ->paginate(20);

        return view('admin.users.global', compact('users'));
    }

    public function companyIndex(Request $request, Company $company)
    {
        $users = User::query()
            ->whereHas('companies', fn ($q) => $q->where('companies.id', $company->id))
            ->when($request->filled('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"))
            ->latest()
            ->paginate(20);

        return view('admin.users.index', compact('company', 'users'));
    }

    public function create()
    {
        $companies = Company::all();
        $branches = Branch::all();

        return view('admin.users.create', compact('companies', 'branches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:50'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'locale' => ['nullable', 'string', 'max:10'],
            'is_active' => ['boolean'],
            'companies' => ['array'],
            'companies.*' => ['integer', 'exists:companies,id'],
            'branches' => ['array'],
            'branches.*' => ['integer', 'exists:branches,id'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'],
            'timezone' => $validated['timezone'],
            'locale' => $validated['locale'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        if (! empty($validated['companies'])) {
            foreach ($validated['companies'] as $companyId) {
                $user->companies()->attach($companyId, ['access_level' => 'staff']);
            }
        }

        if (! empty($validated['branches'])) {
            foreach ($validated['branches'] as $branchId) {
                $branch = Branch::findOrFail($branchId);
                $user->branches()->attach($branchId, ['access_level' => 'staff', 'company_id' => $branch->company_id]);
            }
        }

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function show(User $user)
    {
        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $companies = Company::all();
        $branches = Branch::all();

        $userCompanies = $user->companies()->pluck('companies.id')->toArray();
        $userBranches = $user->branches()->pluck('branches.id')->toArray();

        return view('admin.users.edit', compact('user', 'companies', 'branches', 'userCompanies', 'userBranches'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:50'],
            'avatar' => ['nullable', 'string', 'max:500'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'locale' => ['nullable', 'string', 'max:10'],
            'is_active' => ['boolean'],
            'companies' => ['array'],
            'companies.*' => ['integer', 'exists:companies,id'],
            'branches' => ['array'],
            'branches.*' => ['integer', 'exists:branches,id'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'avatar' => $validated['avatar'],
            'timezone' => $validated['timezone'],
            'locale' => $validated['locale'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $user->companies()->sync($validated['companies'] ?? []);
        $user->branches()->sync($validated['branches'] ?? []);

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }
}
