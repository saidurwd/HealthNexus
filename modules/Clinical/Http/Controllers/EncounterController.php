<?php

namespace Modules\Clinical\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Encounter;
use App\Models\Patient;
use App\Services\EncounterService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Modules\Clinical\Http\Requests\StoreEncounterRequest;
use Modules\Clinical\Http\Requests\UpdateEncounterRequest;

class EncounterController extends Controller
{
    public function __construct(private EncounterService $encounterService) {}

    public function index(Request $request)
    {
        $companyId = app(TenantContextResolver::class)->getCompanyId();

        $encounters = Encounter::query()
            ->when($companyId, fn ($q, $companyId) => $q->where('company_id', $companyId))
            ->when($request->filled('patient_id'), fn ($q, $patientId) => $q->where('patient_id', $patientId))
            ->when($request->filled('type'), fn ($q, $type) => $q->where('encounter_type', $type))
            ->latest()
            ->paginate(20);

        return view('admin.encounters.index', compact('encounters'));
    }

    public function create()
    {
        $companies = Company::all();
        $branches = Branch::all();
        $patients = Patient::all();

        return view('admin.encounters.create', compact('companies', 'branches', 'patients'));
    }

    public function store(StoreEncounterRequest $request)
    {
        $validated = $request->validated();

        $this->encounterService->createEncounter($validated, $request->user());

        return redirect()->route('admin.encounters.index')->with('success', 'Encounter created successfully.');
    }

    public function show(Encounter $encounter)
    {
        $this->authorize('view', $encounter);

        return view('admin.encounters.show', compact('encounter'));
    }

    public function edit(Encounter $encounter)
    {
        $this->authorize('update', $encounter);

        $companies = Company::all();
        $branches = Branch::all();
        $patients = Patient::all();

        return view('admin.encounters.edit', compact('encounter', 'companies', 'branches', 'patients'));
    }

    public function update(UpdateEncounterRequest $request, Encounter $encounter)
    {
        $this->authorize('update', $encounter);

        $validated = $request->validated();

        $this->encounterService->updateEncounter($encounter, $validated);

        return redirect()->route('admin.encounters.index')->with('success', 'Encounter updated successfully.');
    }

    public function destroy(Encounter $encounter)
    {
        $this->authorize('delete', $encounter);

        $encounter->delete();

        return redirect()->route('admin.encounters.index')->with('success', 'Encounter deleted successfully.');
    }
}
