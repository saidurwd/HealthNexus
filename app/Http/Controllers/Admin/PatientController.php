<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePatientRequest;
use App\Http\Requests\Admin\UpdatePatientRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Patient;
use App\Services\AuditLogger;
use App\Services\Patients\PatientService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function __construct(private PatientService $patientService, private AuditLogger $auditLogger) {}

    public function index(Request $request)
    {
        $companyId = app(TenantContextResolver::class)->getCompanyId();

        $patients = Patient::query()
            ->when($companyId, fn ($q, $companyId) => $q->where('company_id', $companyId))
            ->when($request->filled('search'), fn ($q, $search) => $q->where(function ($q2) use ($search) {
                $q2->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('enterprise_patient_no', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(20);

        return view('admin.patients.index', compact('patients'));
    }

    public function create()
    {
        $companies = Company::all();
        $branches = Branch::all();

        return view('admin.patients.create', compact('companies', 'branches'));
    }

    public function store(StorePatientRequest $request)
    {
        $validated = $request->validated();

        $user = $request->user();
        $branch = $validated['branch_id'] ? Branch::findOrFail($validated['branch_id']) : null;

        $patient = $this->patientService->createPatient($validated, $user, $branch);

        $this->auditLogger->log('CREATE', Patient::class, $patient->id, null, $patient->toArray(), $request);

        return redirect()->route('admin.patients.index')->with('success', 'Patient created successfully.');
    }

    public function show(Patient $patient)
    {
        $this->authorize('view', $patient);

        return view('admin.patients.show', compact('patient'));
    }

    public function edit(Patient $patient)
    {
        $this->authorize('update', $patient);

        $companies = Company::all();
        $branches = Branch::all();

        return view('admin.patients.edit', compact('patient', 'companies', 'branches'));
    }

    public function update(UpdatePatientRequest $request, Patient $patient)
    {
        $this->authorize('update', $patient);

        $validated = $request->validated();

        $this->patientService->updatePatient($patient, $validated);

        $this->auditLogger->log('UPDATE', Patient::class, $patient->id, $patient->getOriginal(), $patient->toArray(), $request);

        return redirect()->route('admin.patients.index')->with('success', 'Patient updated successfully.');
    }

    public function destroy(Request $request, Patient $patient)
    {
        $this->authorize('delete', $patient);

        $oldValues = $patient->toArray();

        $patient->delete();

        $this->auditLogger->log('DELETE', Patient::class, $patient->id, $oldValues, null, $request);

        return redirect()->route('admin.patients.index')->with('success', 'Patient deleted successfully.');
    }
}
