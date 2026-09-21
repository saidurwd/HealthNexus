<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request, Company $company, Branch $branch)
    {
        $departments = Department::query()
            ->where('company_id', $company->id)
            ->where('branch_id', $branch->id)
            ->when($request->filled('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%"))
            ->latest()
            ->paginate(20);

        return view('admin.departments.index', compact('company', 'branch', 'departments'));
    }

    public function globalIndex(Request $request)
    {
        $user = auth()->user();

        $departments = Department::query()
            ->whereHas('branch', function ($q) use ($user) {
                $q->whereHas('company', function ($q2) use ($user) {
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

    public function create(Company $company, Branch $branch)
    {
        return view('admin.departments.create', compact('company', 'branch'));
    }

    public function store(Request $request, Company $company, Branch $branch)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:departments,code,NULL,id,company_id,'.$company->id.',branch_id,'.$branch->id],
            'description' => ['nullable', 'string'],
            'head_of_department' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        $validated['company_id'] = $company->id;

        $branch->departments()->create($validated);

        return redirect()->route('admin.companies.branches.departments.index', [$company, $branch])->with('success', 'Department created successfully.');
    }

    public function show(Company $company, Branch $branch, Department $department)
    {
        abort_if($department->branch_id !== $branch->id || $department->company_id !== $company->id, 404);

        return view('admin.departments.show', compact('company', 'branch', 'department'));
    }

    public function edit(Company $company, Branch $branch, Department $department)
    {
        abort_if($department->branch_id !== $branch->id || $department->company_id !== $company->id, 404);

        return view('admin.departments.edit', compact('company', 'branch', 'department'));
    }

    public function update(Request $request, Company $company, Branch $branch, Department $department)
    {
        abort_if($department->branch_id !== $branch->id || $department->company_id !== $company->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:departments,code,'.$department->id.',id,company_id,'.$company->id.',branch_id,'.$branch->id],
            'description' => ['nullable', 'string'],
            'head_of_department' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        $department->update($validated);

        return redirect()->route('admin.companies.branches.departments.index', [$company, $branch])->with('success', 'Department updated successfully.');
    }

    public function destroy(Company $company, Branch $branch, Department $department)
    {
        abort_if($department->branch_id !== $branch->id || $department->company_id !== $company->id, 404);

        $department->delete();

        return redirect()->route('admin.companies.branches.departments.index', [$company, $branch])->with('success', 'Department deleted successfully.');
    }
}
