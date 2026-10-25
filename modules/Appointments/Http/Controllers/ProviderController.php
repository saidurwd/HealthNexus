<?php

namespace Modules\Appointments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\Provider;
use App\Models\Specialty;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

class ProviderController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function index(Request $request)
    {
        $this->authorize('manageProvider', Appointment::class);

        $organizationId = app(TenantContextResolver::class)->getOrganizationId();

        $providers = Provider::where('organization_id', $organizationId)
            ->when($request->filled('search'), fn ($q, $search = null) => $q->where('name', 'like', '%'.$request->input('search').'%'))
            ->with(['department', 'specialty', 'user'])
            ->orderBy('name')
            ->paginate(20);

        return view('admin.providers.index', compact('providers'));
    }

    public function create()
    {
        $this->authorize('manageProvider', Appointment::class);

        $organizationId = app(TenantContextResolver::class)->getOrganizationId();
        $users = User::whereHas('organizations', fn ($q) => $q->where('organizations.id', $organizationId))->get();
        $departments = Department::where('organization_id', $organizationId)->get();
        $specialties = Specialty::where('organization_id', $organizationId)->where('is_active', true)->get();

        return view('admin.providers.create', compact('users', 'departments', 'specialties'));
    }

    public function store(Request $request)
    {
        $this->authorize('manageProvider', Appointment::class);

        $validated = $this->validated($request);
        $validated['organization_id'] = app(TenantContextResolver::class)->getOrganizationId();
        $validated['branch_id'] = app(TenantContextResolver::class)->getBranchId();

        $provider = Provider::create($validated);

        $this->auditLogger->log('CREATE', Provider::class, $provider->id, null, $provider->toArray(), $request);

        return redirect()->route('admin.providers.index')->with('success', 'Provider created successfully.');
    }

    public function show(Provider $provider)
    {
        $this->authorize('manageProvider', Appointment::class);

        $provider->load(['department', 'specialty', 'user', 'schedules']);

        return view('admin.providers.show', compact('provider'));
    }

    public function edit(Provider $provider)
    {
        $this->authorize('manageProvider', Appointment::class);

        $organizationId = app(TenantContextResolver::class)->getOrganizationId();
        $users = User::whereHas('organizations', fn ($q) => $q->where('organizations.id', $organizationId))->get();
        $departments = Department::where('organization_id', $organizationId)->get();
        $specialties = Specialty::where('organization_id', $organizationId)->where('is_active', true)->get();

        return view('admin.providers.edit', compact('provider', 'users', 'departments', 'specialties'));
    }

    public function update(Request $request, Provider $provider)
    {
        $this->authorize('manageProvider', Appointment::class);

        $validated = $this->validated($request);
        $oldValues = $provider->toArray();
        $provider->update($validated);

        $this->auditLogger->log('UPDATE', Provider::class, $provider->id, $oldValues, $provider->toArray(), $request);

        return redirect()->route('admin.providers.index')->with('success', 'Provider updated successfully.');
    }

    public function destroy(Provider $provider)
    {
        $this->authorize('manageProvider', Appointment::class);

        $oldValues = $provider->toArray();
        $provider->delete();

        $this->auditLogger->log('DELETE', Provider::class, $provider->id, $oldValues, null, request());

        return redirect()->route('admin.providers.index')->with('success', 'Provider deleted successfully.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'provider_code' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'provider_type' => ['required', 'string', 'max:100'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'specialty_id' => ['nullable', 'integer', 'exists:specialties,id'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:active,inactive'],
        ]);
    }
}
