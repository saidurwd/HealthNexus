<?php

namespace Modules\Core\Organization\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Organization;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request, Organization $organization)
    {
        $branches = Branch::query()
            ->where('organization_id', $organization->id)
            ->when($request->filled('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%"))
            ->latest()
            ->paginate(20);

        return view('admin.branches.index', compact('organization', 'branches'));
    }

    public function create(Organization $organization)
    {
        return view('admin.branches.create', compact('organization'));
    }

    public function store(Request $request, Organization $organization)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:branches,code,NULL,id,organization_id,'.$organization->id],
            'slug' => ['required', 'string', 'max:255', 'unique:branches,slug,NULL,id,organization_id,'.$organization->id],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $organization->branches()->create($validated);

        return redirect()->route('admin.organizations.branches.index', $organization)->with('success', __('Branch created successfully.'));
    }

    public function show(Organization $organization, Branch $branch)
    {
        abort_if($branch->organization_id !== $organization->id, 404);

        return view('admin.branches.show', compact('organization', 'branch'));
    }

    public function edit(Organization $organization, Branch $branch)
    {
        abort_if($branch->organization_id !== $organization->id, 404);

        return view('admin.branches.edit', compact('organization', 'branch'));
    }

    public function update(Request $request, Organization $organization, Branch $branch)
    {
        abort_if($branch->organization_id !== $organization->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:branches,code,'.$branch->id.',id,organization_id,'.$organization->id],
            'slug' => ['required', 'string', 'max:255', 'unique:branches,slug,'.$branch->id.',id,organization_id,'.$organization->id],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $branch->update($validated);

        return redirect()->route('admin.organizations.branches.index', $organization)->with('success', __('Branch updated successfully.'));
    }

    public function destroy(Organization $organization, Branch $branch)
    {
        abort_if($branch->organization_id !== $organization->id, 404);

        $branch->delete();

        return redirect()->route('admin.organizations.branches.index', $organization)->with('success', __('Branch deleted successfully.'));
    }
}
