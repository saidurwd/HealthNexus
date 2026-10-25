<?php

namespace Modules\Core\Organization\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Organization;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request, Organization $organization, Branch $branch)
    {
        $departments = Department::query()
            ->where('organization_id', $organization->id)
            ->where('branch_id', $branch->id)
            ->when($request->filled('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%"))
            ->latest()
            ->paginate(20);

        return view('admin.departments.index', compact('organization', 'branch', 'departments'));
    }

    public function globalIndex(Request $request)
    {
        $user = auth()->user();

        $departments = Department::query()
            ->whereHas('branch', function ($q) use ($user) {
                $q->whereHas('organization', function ($q2) use ($user) {
                    $q2->whereHas('users', function ($q3) use ($user) {
                        $q3->where('user_id', $user->id);
                    });
                });
            })
            ->when($request->filled('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%"))
            ->latest()
            ->paginate(20);

        return view('admin.departments.global', compact('departments'));
    }

    public function create(Organization $organization, Branch $branch)
    {
        return view('admin.departments.create', compact('organization', 'branch'));
    }

    public function createGlobal()
    {
        $user = auth()->user();
        $organizations = Organization::query()
            ->whereHas('users', fn ($q) => $q->where('user_id', $user->id))
            ->where('is_active', true)
            ->get();

        return view('admin.departments.create-global', compact('organizations'));
    }

    public function store(Request $request, Organization $organization, Branch $branch)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:departments,code,NULL,id,organization_id,'.$organization->id.',branch_id,'.$branch->id],
            'description' => ['nullable', 'string'],
            'head_of_department' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        $validated['organization_id'] = $organization->id;

        $branch->departments()->create($validated);

        return redirect()->route('admin.organizations.branches.departments.index', [$organization, $branch])->with('success', 'Department created successfully.');
    }

    public function show(Organization $organization, Branch $branch, Department $department)
    {
        abort_if($department->branch_id !== $branch->id || $department->organization_id !== $organization->id, 404);

        return view('admin.departments.show', compact('organization', 'branch', 'department'));
    }

    public function edit(Organization $organization, Branch $branch, Department $department)
    {
        abort_if($department->branch_id !== $branch->id || $department->organization_id !== $organization->id, 404);

        return view('admin.departments.edit', compact('organization', 'branch', 'department'));
    }

    public function update(Request $request, Organization $organization, Branch $branch, Department $department)
    {
        abort_if($department->branch_id !== $branch->id || $department->organization_id !== $organization->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:departments,code,'.$department->id.',id,organization_id,'.$organization->id.',branch_id,'.$branch->id],
            'description' => ['nullable', 'string'],
            'head_of_department' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        $department->update($validated);

        return redirect()->route('admin.organizations.branches.departments.index', [$organization, $branch])->with('success', 'Department updated successfully.');
    }

    public function destroy(Organization $organization, Branch $branch, Department $department)
    {
        abort_if($department->branch_id !== $branch->id || $department->organization_id !== $organization->id, 404);

        $department->delete();

        return redirect()->route('admin.organizations.branches.departments.index', [$organization, $branch])->with('success', 'Department deleted successfully.');
    }
}
