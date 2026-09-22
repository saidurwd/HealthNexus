<?php

namespace Modules\Patients\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\IdentificationType;
use App\Models\Patient;
use App\Models\PatientAlert;
use App\Models\PatientAllergy;
use App\Models\PatientDocument;
use App\Models\PatientHistory;
use App\Services\AuditLogger;
use App\Services\Patients\PatientService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Modules\Patients\Http\Requests\StorePatientRequest;
use Modules\Patients\Http\Requests\UpdatePatientRequest;

class PatientController extends Controller
{
    public function __construct(
        private PatientService $patientService,
        private AuditLogger $auditLogger
    ) {}

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
        $identificationTypes = IdentificationType::all();

        return view('admin.patients.create', compact('companies', 'branches', 'identificationTypes'));
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

        $patient->load(['identifiers', 'contacts', 'allergies', 'histories', 'documents', 'encounters']);

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

        $oldValues = $patient->toArray();

        $this->patientService->updatePatient($patient, $validated);

        $this->auditLogger->log('UPDATE', Patient::class, $patient->id, $oldValues, $patient->toArray(), $request);

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

    public function search(Request $request)
    {
        $this->authorize('viewAny', Patient::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();

        $search = $request->input('q', '');

        $patients = Patient::query()
            ->when($companyId, fn ($q, $companyId) => $q->where('company_id', $companyId))
            ->when($search, function ($q, $search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('enterprise_patient_no', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('national_identifier', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->appends($request->only(['q']));

        return view('admin.patients.search', compact('patients', 'search'));
    }

    public function detectDuplicates(Request $request)
    {
        $this->authorize('viewAny', Patient::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();

        $firstName = $request->input('first_name', '');
        $lastName = $request->input('last_name', '');
        $phone = $request->input('phone');
        $nationalId = $request->input('national_identifier');
        $email = $request->input('email');

        $duplicates = $this->patientService->detectDuplicates($companyId, $firstName, $lastName, $phone, $nationalId, $email);

        return response()->json([
            'duplicates' => $duplicates->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->full_name,
                'enterprise_patient_no' => $p->enterprise_patient_no,
                'phone' => $p->phone,
                'national_identifier' => $p->national_identifier,
            ]),
        ]);
    }

    public function merge(Request $request)
    {
        $this->authorize('update', new Patient);

        $masterPatient = Patient::findOrFail($request->input('master_patient_id'));
        $duplicatePatient = Patient::findOrFail($request->input('duplicate_patient_id'));

        $oldValues = $duplicatePatient->toArray();

        $this->patientService->mergePatients($masterPatient, $duplicatePatient);

        $this->auditLogger->log('MERGE', Patient::class, $duplicatePatient->id, $oldValues, ['merged_into' => $masterPatient->id], $request);

        return redirect()->route('admin.patients.show', $masterPatient)->with('success', 'Patients merged successfully.');
    }

    public function timeline(Patient $patient)
    {
        $this->authorize('view', $patient);

        $timeline = $this->patientService->getTimeline($patient);

        return view('admin.patients.timeline', compact('patient', 'timeline'));
    }

    public function documents(Request $request, Patient $patient)
    {
        $this->authorize('view', $patient);

        $documents = $patient->documents()->paginate(20);

        return view('admin.patients.documents', compact('patient', 'documents'));
    }

    public function uploadDocument(Request $request, Patient $patient)
    {
        $this->authorize('update', $patient);

        $validated = $request->validate([
            'document' => ['required', 'file', 'max:10240'],
            'document_type' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ]);

        $file = $request->file('document');

        $path = $file->store('patient-documents', 'public');

        $document = PatientDocument::create([
            'company_id' => $patient->company_id,
            'patient_id' => $patient->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'document_type' => $validated['document_type'],
            'description' => $validated['description'],
            'uploaded_by' => $request->user()->id,
        ]);

        $this->auditLogger->log('UPLOAD', PatientDocument::class, $document->id, null, $document->toArray(), $request);

        return redirect()->route('admin.patients.documents', $patient)->with('success', 'Document uploaded successfully.');
    }

    public function deleteDocument(Patient $patient, PatientDocument $document)
    {
        $this->authorize('update', $patient);

        $oldValues = $document->toArray();

        $document->delete();

        $this->auditLogger->log('DELETE', PatientDocument::class, $document->id, $oldValues, null, request());

        return redirect()->route('admin.patients.documents', $patient)->with('success', 'Document deleted successfully.');
    }

    public function allergies(Patient $patient)
    {
        $this->authorize('view', $patient);

        $allergies = $patient->allergies()->latest()->get();

        return view('admin.patients.allergies', compact('patient', 'allergies'));
    }

    public function addAllergy(Request $request, Patient $patient)
    {
        $this->authorize('update', $patient);

        $validated = $request->validate([
            'substance' => ['required', 'string', 'max:255'],
            'severity' => ['nullable', 'in:mild,moderate,severe'],
            'reaction' => ['nullable', 'in:rash,hives,itching,swelling,anaphylaxis,other'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $allergy = $this->patientService->addAllergy($patient, $validated);

        $this->auditLogger->log('CREATE', PatientAllergy::class, $allergy->id, null, $allergy->toArray(), $request);

        return redirect()->route('admin.patients.allergies', $patient)->with('success', 'Allergy added successfully.');
    }

    public function deleteAllergy(Patient $patient, PatientAllergy $allergy)
    {
        $this->authorize('update', $patient);

        $oldValues = $allergy->toArray();

        $allergy->delete();

        $this->auditLogger->log('DELETE', PatientAllergy::class, $allergy->id, $oldValues, null, request());

        return redirect()->route('admin.patients.allergies', $patient)->with('success', 'Allergy removed successfully.');
    }

    public function history(Patient $patient)
    {
        $this->authorize('view', $patient);

        $histories = $patient->histories()->latest()->get();

        return view('admin.patients.history', compact('patient', 'histories'));
    }

    public function addHistory(Request $request, Patient $patient)
    {
        $this->authorize('update', $patient);

        $validated = $request->validate([
            'condition' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'diagnosed_at' => ['nullable', 'date'],
            'resolved_at' => ['nullable', 'date', 'after_or_equal:diagnosed_at'],
            'is_active' => ['boolean'],
        ]);

        $history = $this->patientService->addHistory($patient, $validated);

        $this->auditLogger->log('CREATE', PatientHistory::class, $history->id, null, $history->toArray(), $request);

        return redirect()->route('admin.patients.history', $patient)->with('success', 'Medical history added successfully.');
    }

    public function deleteHistory(Patient $patient, PatientHistory $history)
    {
        $this->authorize('update', $patient);

        $oldValues = $history->toArray();

        $history->delete();

        $this->auditLogger->log('DELETE', PatientHistory::class, $history->id, $oldValues, null, request());

        return redirect()->route('admin.patients.history', $patient)->with('success', 'Medical history removed successfully.');
    }

    public function alerts(Patient $patient)
    {
        $this->authorize('view', $patient);

        $alerts = $patient->alerts()->latest()->get();

        return view('admin.patients.alerts', compact('patient', 'alerts'));
    }

    public function addAlert(Request $request, Patient $patient)
    {
        $this->authorize('update', $patient);

        $validated = $request->validate([
            'alert_type' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'severity' => ['required', 'in:info,warning,critical'],
            'status' => ['sometimes', 'in:active,inactive,resolved,expired'],
            'start_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:start_at'],
        ]);

        $alert = $this->patientService->addAlert($patient, $validated);

        $this->auditLogger->log('CREATE', PatientAlert::class, $alert->id, null, $alert->toArray(), $request);

        return redirect()->route('admin.patients.alerts', $patient)->with('success', 'Patient alert added successfully.');
    }

    public function resolveAlert(Request $request, Patient $patient, PatientAlert $alert)
    {
        $this->authorize('update', $patient);

        $validated = $request->validate([
            'resolution_note' => ['nullable', 'string'],
        ]);

        $oldValues = $alert->toArray();

        $this->patientService->resolveAlert($alert, $validated);

        $this->auditLogger->log('UPDATE', PatientAlert::class, $alert->id, $oldValues, $alert->fresh()->toArray(), $request);

        return redirect()->route('admin.patients.alerts', $patient)->with('success', 'Patient alert resolved.');
    }

    public function deleteAlert(Request $request, Patient $patient, PatientAlert $alert)
    {
        $this->authorize('update', $patient);

        $oldValues = $alert->toArray();

        $alert->delete();

        $this->auditLogger->log('DELETE', PatientAlert::class, $alert->id, $oldValues, null, $request);

        return redirect()->route('admin.patients.alerts', $patient)->with('success', 'Patient alert removed successfully.');
    }
}
