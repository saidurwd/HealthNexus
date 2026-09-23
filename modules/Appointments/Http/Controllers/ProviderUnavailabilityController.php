<?php

namespace Modules\Appointments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Provider;
use App\Models\ProviderUnavailability;
use App\Services\AuditLogger;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

/**
 * Covers both "Leave" and "Block Schedule" from the spec (§20/§22/§31/§32) — both are
 * structurally a provider being unavailable for a time range, so they're consolidated onto one
 * table/controller rather than duplicating near-identical concepts.
 */
class ProviderUnavailabilityController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function index(Request $request)
    {
        $this->authorize('manageBlock', Appointment::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();

        $entries = ProviderUnavailability::where('company_id', $companyId)
            ->when($request->filled('provider_id'), fn ($q, $id = null) => $q->where('provider_id', $request->input('provider_id')))
            ->with('provider')
            ->latest('start_at')
            ->paginate(20);

        $providers = Provider::where('company_id', $companyId)->where('status', 'active')->get();

        return view('admin.provider-unavailability.index', compact('entries', 'providers'));
    }

    public function store(Request $request)
    {
        $this->authorize('manageBlock', Appointment::class);

        $validated = $request->validate([
            'provider_id' => ['required', 'integer', 'exists:providers,id'],
            'reason_type' => ['required', 'in:leave,training,meeting,conference,personal,emergency,other'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['company_id'] = app(TenantContextResolver::class)->getCompanyId();
        $validated['branch_id'] = app(TenantContextResolver::class)->getBranchId();
        $validated['created_by'] = $request->user()->id;

        $entry = ProviderUnavailability::create($validated);

        $this->auditLogger->log('CREATE', ProviderUnavailability::class, $entry->id, null, $entry->toArray(), $request);

        return redirect()->route('admin.provider-unavailability.index')->with('success', 'Unavailability recorded — new bookings in this window are now blocked.');
    }

    public function destroy(ProviderUnavailability $unavailability)
    {
        $this->authorize('manageBlock', Appointment::class);

        $oldValues = $unavailability->toArray();
        $unavailability->delete();

        $this->auditLogger->log('DELETE', ProviderUnavailability::class, $unavailability->id, $oldValues, null, request());

        return redirect()->route('admin.provider-unavailability.index')->with('success', 'Unavailability removed.');
    }
}
