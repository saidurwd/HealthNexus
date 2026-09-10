<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request, Company $company)
    {
        $users = User::query()
            ->whereHas('companies', fn ($q) => $q->where('companies.id', $company->id))
            ->when($request->filled('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"))
            ->latest()
            ->paginate(20);

        return view('admin.users.index', compact('company', 'users'));
    }

    public function show(Company $company, User $user)
    {
        abort_if(! $user->companies()->where('companies.id', $company->id)->exists(), 404);

        return view('admin.users.show', compact('company', 'user'));
    }

    public function edit(Company $company, User $user)
    {
        abort_if(! $user->companies()->where('companies.id', $company->id)->exists(), 404);

        return view('admin.users.edit', compact('company', 'user'));
    }

    public function update(Request $request, Company $company, User $user)
    {
        abort_if(! $user->companies()->where('companies.id', $company->id)->exists(), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'avatar' => ['nullable', 'string', 'max:500'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'locale' => ['nullable', 'string', 'max:10'],
            'is_active' => ['boolean'],
        ]);

        $user->update($validated);

        return redirect()->route('admin.companies.users.index', $company)->with('success', 'User updated successfully.');
    }
}
