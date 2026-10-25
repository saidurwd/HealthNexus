<?php

namespace Modules\Core\Organization\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function index(Request $request)
    {
        $organizations = Organization::query()
            ->withCount('branches')
            ->when($request->filled('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%"))
            ->when($request->filled('status'), fn ($q, $status) => $q->where('is_active', $status === 'active'))
            ->latest()
            ->paginate(20);

        return view('admin.organizations.index', compact('organizations'));
    }

    public function create()
    {
        return view('admin.organizations.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:organizations,code'],
            'slug' => ['required', 'string', 'max:255', 'unique:organizations,slug'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $organization = Organization::create($validated);

        $this->auditLogger->log('CREATE', Organization::class, $organization->id, null, $organization->toArray(), $request);

        return redirect()->route('admin.organizations.index')->with('success', 'Organization created successfully.');
    }

    public function show(Organization $organization)
    {
        return view('admin.organizations.show', compact('organization'));
    }

    public function edit(Organization $organization)
    {
        return view('admin.organizations.edit', compact('organization'));
    }

    public function update(Request $request, Organization $organization)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:organizations,code,'.$organization->id],
            'slug' => ['required', 'string', 'max:255', 'unique:organizations,slug,'.$organization->id],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $oldValues = $organization->toArray();
        $organization->update($validated);

        $this->auditLogger->log('UPDATE', Organization::class, $organization->id, $oldValues, $organization->toArray(), $request);

        return redirect()->route('admin.organizations.index')->with('success', 'Organization updated successfully.');
    }

    public function destroy(Organization $organization)
    {
        $oldValues = $organization->toArray();
        $organization->delete();

        $this->auditLogger->log('DELETE', Organization::class, $organization->id, $oldValues, null, $request);

        return redirect()->route('admin.organizations.index')->with('success', 'Organization deleted successfully.');
    }
}
