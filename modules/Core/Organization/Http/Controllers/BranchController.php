<?php

namespace Modules\Core\Organization\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request, Company $company)
    {
        $branches = Branch::query()
            ->where('company_id', $company->id)
            ->when($request->filled('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%"))
            ->latest()
            ->paginate(20);

        return view('admin.branches.index', compact('company', 'branches'));
    }

    public function create(Company $company)
    {
        return view('admin.branches.create', compact('company'));
    }

    public function store(Request $request, Company $company)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:branches,code,NULL,id,company_id,'.$company->id],
            'slug' => ['required', 'string', 'max:255', 'unique:branches,slug,NULL,id,company_id,'.$company->id],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $company->branches()->create($validated);

        return redirect()->route('admin.companies.branches.index', $company)->with('success', 'Branch created successfully.');
    }

    public function show(Company $company, Branch $branch)
    {
        abort_if($branch->company_id !== $company->id, 404);

        return view('admin.branches.show', compact('company', 'branch'));
    }

    public function edit(Company $company, Branch $branch)
    {
        abort_if($branch->company_id !== $company->id, 404);

        return view('admin.branches.edit', compact('company', 'branch'));
    }

    public function update(Request $request, Company $company, Branch $branch)
    {
        abort_if($branch->company_id !== $company->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:branches,code,'.$branch->id.',id,company_id,'.$company->id],
            'slug' => ['required', 'string', 'max:255', 'unique:branches,slug,'.$branch->id.',id,company_id,'.$company->id],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $branch->update($validated);

        return redirect()->route('admin.companies.branches.index', $company)->with('success', 'Branch updated successfully.');
    }

    public function destroy(Company $company, Branch $branch)
    {
        abort_if($branch->company_id !== $company->id, 404);

        $branch->delete();

        return redirect()->route('admin.companies.branches.index', $company)->with('success', 'Branch deleted successfully.');
    }
}
