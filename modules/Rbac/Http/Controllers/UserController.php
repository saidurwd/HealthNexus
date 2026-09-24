<?php

namespace Modules\Rbac\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(private FileUploadService $fileUploadService) {}

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

    /**
     * Roles the acting user may grant. super_admin is never grantable by anyone who is not
     * already a super_admin, so this screen cannot be used for privilege escalation.
     */
    private function assignableRoles(Request $request)
    {
        return Role::query()
            ->when(! $request->user()->hasRole('super_admin'), fn ($q) => $q->where('name', '!=', 'super_admin'))
            ->orderBy('name')
            ->get();
    }

    /**
     * Syncs only the roles the actor is allowed to manage — a super_admin role already held by
     * the target is preserved when the actor is not a super_admin.
     */
    private function syncUserRoles(Request $request, User $user, array $submitted): void
    {
        $assignable = $this->assignableRoles($request)->pluck('name')->all();
        $granted = array_values(array_intersect($submitted, $assignable));

        if ($user->hasRole('super_admin') && ! in_array('super_admin', $assignable, true)) {
            $granted[] = 'super_admin';
        }

        $user->syncRoles($granted);
    }

    public function create(Request $request)
    {
        $companies = Company::all();
        $branches = Branch::all();
        $roles = $this->assignableRoles($request);

        return view('admin.users.create', compact('companies', 'branches', 'roles'));
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
            'profile_picture' => ['nullable', 'image', 'max:2048'],
            'companies' => ['array'],
            'companies.*' => ['integer', 'exists:companies,id'],
            'branches' => ['array'],
            'branches.*' => ['integer', 'exists:branches,id'],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::in($this->assignableRoles($request)->pluck('name')->all())],
        ]);

        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'timezone' => $validated['timezone'] ?? 'UTC',
            'locale' => $validated['locale'] ?? 'en',
            'is_active' => $validated['is_active'] ?? true,
        ];

        if ($request->hasFile('profile_picture')) {
            $userData['profile_picture'] = $this->fileUploadService->upload($request->file('profile_picture'), 'profile-pictures', 'public');
        }

        $user = User::create($userData);

        $this->syncUserRoles($request, $user, $validated['roles'] ?? []);

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

    public function edit(Request $request, User $user)
    {
        $companies = Company::all();
        $branches = Branch::all();
        $roles = $this->assignableRoles($request);
        $userRoles = $user->roles()->pluck('name')->toArray();

        $userCompanies = $user->companies()->pluck('companies.id')->toArray();
        $userBranches = $user->branches()->pluck('branches.id')->toArray();

        return view('admin.users.edit', compact('user', 'companies', 'branches', 'userCompanies', 'userBranches', 'roles', 'userRoles'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:50'],
            'avatar' => ['nullable', 'string', 'max:500'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'locale' => ['nullable', 'string', 'max:10'],
            'is_active' => ['boolean'],
            'profile_picture' => ['nullable', 'image', 'max:2048'],
            'companies' => ['array'],
            'companies.*' => ['integer', 'exists:companies,id'],
            'branches' => ['array'],
            'branches.*' => ['integer', 'exists:branches,id'],
            'roles_present' => ['nullable', 'boolean'],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::in($this->assignableRoles($request)->pluck('name')->all())],
        ]);

        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'avatar' => $validated['avatar'] ?? null,
            'timezone' => $validated['timezone'] ?? 'UTC',
            'locale' => $validated['locale'] ?? 'en',
            'is_active' => $validated['is_active'] ?? true,
        ];

        if ($request->hasFile('profile_picture')) {
            $userData['profile_picture'] = $this->fileUploadService->upload($request->file('profile_picture'), 'profile-pictures', 'public');
        }

        $user->update($userData);

        $user->companies()->sync($validated['companies'] ?? []);
        $user->branches()->sync($validated['branches'] ?? []);

        if ($request->boolean('roles_present')) {
            $this->syncUserRoles($request, $user, $validated['roles'] ?? []);
        }

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }
}
