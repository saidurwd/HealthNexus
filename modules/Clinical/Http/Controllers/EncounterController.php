<?php

namespace Modules\Clinical\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Encounter;
use App\Models\Patient;
use App\Services\EncounterLifecycleService;
use App\Services\EncounterService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Modules\Clinical\Http\Requests\StoreEncounterRequest;
use Modules\Clinical\Http\Requests\UpdateEncounterRequest;

class EncounterController extends Controller
{
    public function __construct(
        private EncounterService $encounterService,
        private EncounterLifecycleService $lifecycleService
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Encounter::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $encounters = Encounter::query()
            ->when($companyId, fn ($q, $companyId) => $q->where('company_id', $companyId))
            ->when($branchId, fn ($q, $branchId) => $q->where('branch_id', $branchId))
            ->when($request->filled('patient_id'), fn ($q, $patientId) => $q->where('patient_id', $request->input('patient_id')))
            ->when($request->filled('type'), fn ($q, $type) => $q->where('encounter_type', $request->input('type')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('provider_id'), fn ($q) => $q->where('provider_id', $request->input('provider_id')))
            ->with(['patient', 'provider', 'encounterType'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.encounters.index', compact('encounters'));
    }

    public function create()
    {
        $this->authorize('create', Encounter::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();

        $branches = Branch::where('company_id', $companyId)->get();
        $patients = Patient::where('company_id', $companyId)->orderBy('first_name')->paginate(50);

        return view('admin.encounters.create', compact('branches', 'patients'));
    }

    public function store(StoreEncounterRequest $request)
    {
        $this->authorize('create', Encounter::class);

        $validated = $request->validated();

        $encounter = $this->encounterService->createEncounter($validated, $request->user());

        return redirect()->route('admin.encounters.show', $encounter)->with('success', 'Encounter created successfully.');
    }

    public function show(Encounter $encounter)
    {
        $this->authorize('view', $encounter);

        $encounter->load([
            'patient.allergies',
            'appointment',
            'encounterType',
            'provider',
            'department',
            'specialty',
            'complaints',
            'histories',
            'examinations',
            'reviewOfSystems',
            'vitals',
            'diagnoses',
            'problems',
            'procedures',
            'orders.items',
            'investigationOrders',
            'prescriptions.items',
            'referrals',
            'instructions',
            'notes',
            'documents',
            'amendments.createdBy',
            'amendments.approvedBy',
            'statusHistory.changer',
        ]);

        return view('admin.encounters.clinical', compact('encounter'));
    }

    public function edit(Encounter $encounter)
    {
        $this->authorize('update', $encounter);

        $companyId = app(TenantContextResolver::class)->getCompanyId();

        $branches = Branch::where('company_id', $companyId)->get();
        $patients = Patient::where('company_id', $companyId)->orderBy('first_name')->paginate(50);

        return view('admin.encounters.edit', compact('encounter', 'branches', 'patients'));
    }

    public function update(UpdateEncounterRequest $request, Encounter $encounter)
    {
        $this->authorize('update', $encounter);

        $validated = $request->validated();

        if (isset($validated['status'])) {
            $this->lifecycleService->moveTo($encounter, $validated['status'], $request->user());

            unset($validated['status']);
        }

        if (! empty($validated)) {
            $this->encounterService->updateEncounter($encounter, $validated);
        }

        return redirect()->route('admin.encounters.show', $encounter)->with('success', 'Encounter updated successfully.');
    }

    public function destroy(Encounter $encounter)
    {
        $this->authorize('delete', $encounter);

        $encounter->delete();

        return redirect()->route('admin.encounters.index')->with('success', 'Encounter deleted successfully.');
    }
}
