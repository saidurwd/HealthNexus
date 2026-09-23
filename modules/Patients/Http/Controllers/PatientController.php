<?php

namespace Modules\Patients\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Country;
use App\Models\Gender;
use App\Models\IdentificationType;
use App\Models\MaritalStatus;
use App\Models\Patient;
use App\Models\PatientAlert;
use App\Models\PatientAllergy;
use App\Models\PatientAmendment;
use App\Models\PatientConsent;
use App\Models\PatientDocument;
use App\Models\PatientDuplicateCandidate;
use App\Models\PatientHistory;
use App\Models\PatientType;
use App\Services\ActivityLogger;
use App\Services\AuditLogger;
use App\Services\FileService;
use App\Services\FileUploadService;
use App\Services\Patients\PatientAmendmentService;
use App\Services\Patients\PatientBarcodeService;
use App\Services\Patients\PatientDuplicateScoringService;
use App\Services\Patients\PatientService;
use App\Services\Patients\PatientTimelineService;
use App\Services\TenantContextResolver;
use App\Support\Fhir\PatientFhirMapper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Patients\Http\Requests\StorePatientRequest;
use Modules\Patients\Http\Requests\UpdatePatientRequest;

class PatientController extends Controller
{
    public function __construct(
        private PatientService $patientService,
        private PatientTimelineService $timeline,
        private PatientDuplicateScoringService $duplicateScoring,
        private PatientAmendmentService $amendmentService,
        private PatientBarcodeService $barcodeService,
        private PatientFhirMapper $fhirMapper,
        private FileService $fileService,
        private AuditLogger $auditLogger,
        private ActivityLogger $activityLogger,
        private FileUploadService $fileUploadService,
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
            ->tap(fn ($q) => $this->applyAdvancedFilters($q, $request))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $this->activityLogger->log('PATIENT_LIST_VIEWED', 'Viewed patient list', null, [], $request);

        return view('admin.patients.index', compact('patients'));
    }

    private function applyAdvancedFilters($query, Request $request): void
    {
        $query
            ->when($request->filled('gender_id'), fn ($q) => $q->where('gender_id', $request->input('gender_id')))
            ->when($request->filled('sex'), fn ($q) => $q->where('sex', $request->input('sex')))
            ->when($request->filled('blood_group'), fn ($q) => $q->where('blood_group', $request->input('blood_group')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('patient_type_id'), fn ($q) => $q->where('patient_type_id', $request->input('patient_type_id')))
            ->when($request->filled('city'), fn ($q) => $q->where('city', 'like', '%'.$request->input('city').'%'))
            ->when($request->filled('registered_from'), fn ($q) => $q->whereDate('registered_at', '>=', $request->input('registered_from')))
            ->when($request->filled('registered_to'), fn ($q) => $q->whereDate('registered_at', '<=', $request->input('registered_to')))
            ->when($request->filled('age_min') || $request->filled('age_max'), function ($q) use ($request) {
                $min = $request->input('age_min');
                $max = $request->input('age_max');

                if ($max !== null && $max !== '') {
                    $q->where('date_of_birth', '>=', now()->subYears((int) $max + 1)->addDay());
                }

                if ($min !== null && $min !== '') {
                    $q->where('date_of_birth', '<=', now()->subYears((int) $min));
                }
            });
    }

    public function create()
    {
        $companies = Company::all();
        $branches = Branch::all();
        $identificationTypes = IdentificationType::all();
        $genders = Gender::where('is_active', true)->orderBy('sort_order')->get();
        $maritalStatuses = MaritalStatus::where('is_active', true)->orderBy('sort_order')->get();
        $patientTypes = PatientType::where('is_active', true)->orderBy('sort_order')->get();
        $countries = Country::orderBy('name')->get();

        return view('admin.patients.create', compact('companies', 'branches', 'identificationTypes', 'genders', 'maritalStatuses', 'patientTypes', 'countries'));
    }

    public function store(StorePatientRequest $request)
    {
        $this->authorize('create', Patient::class);

        $validated = $request->validated();

        $user = $request->user();
        $branch = $validated['branch_id'] ? Branch::findOrFail($validated['branch_id']) : null;

        $patient = $this->patientService->createPatient($validated, $user, $branch);

        $this->auditLogger->log('CREATE', Patient::class, $patient->id, null, $patient->toArray(), $request);

        $this->duplicateScoring->scanForCandidate($patient);

        return redirect()->route('admin.patients.index')->with('success', 'Patient created successfully.');
    }

    public function show(Patient $patient)
    {
        $this->authorize('view', $patient);

        $patient->load(['identifiers', 'contacts', 'allergies', 'histories', 'documents', 'encounters', 'addresses', 'guardians', 'activeConsents', 'preference', 'gender', 'maritalStatus', 'patientType', 'nationality']);

        $this->activityLogger->log('PATIENT_VIEWED', 'Viewed patient record', $patient);

        return view('admin.patients.show', compact('patient'));
    }

    public function edit(Patient $patient)
    {
        $this->authorize('update', $patient);

        $companies = Company::all();
        $branches = Branch::all();
        $genders = Gender::where('is_active', true)->orderBy('sort_order')->get();
        $maritalStatuses = MaritalStatus::where('is_active', true)->orderBy('sort_order')->get();
        $patientTypes = PatientType::where('is_active', true)->orderBy('sort_order')->get();
        $countries = Country::orderBy('name')->get();

        return view('admin.patients.edit', compact('patient', 'companies', 'branches', 'genders', 'maritalStatuses', 'patientTypes', 'countries'));
    }

    /**
     * Sensitive identity fields (configurable via patients.amendment_sensitive_fields) are routed
     * through the amendment approval workflow instead of being written directly; any other
     * changed fields in the same submission are applied immediately.
     */
    public function update(UpdatePatientRequest $request, Patient $patient)
    {
        $this->authorize('update', $patient);

        $validated = $request->validated();
        $oldValues = $patient->toArray();

        $sensitiveFields = array_values(array_filter(
            array_keys($validated),
            fn ($field) => $this->amendmentService->requiresAmendment([$field]) && $this->fieldChanged($patient, $field, $validated[$field] ?? null)
        ));

        if (! empty($sensitiveFields)) {
            $this->authorize('requestAmendment', $patient);

            $proposedChanges = array_intersect_key($validated, array_flip($sensitiveFields));
            $this->amendmentService->request(
                $patient,
                $proposedChanges,
                $request->input('amendment_reason') ?: 'Correction submitted via patient edit form',
                $request->user(),
            );
            $validated = array_diff_key($validated, array_flip($sensitiveFields));
        }

        if (! empty($validated)) {
            $this->patientService->updatePatient($patient, $validated);
        }

        $this->auditLogger->log('UPDATE', Patient::class, $patient->id, $oldValues, $patient->fresh()->toArray(), $request);

        $message = ! empty($sensitiveFields)
            ? 'Patient updated. Sensitive field changes require approval and are pending.'
            : 'Patient updated successfully.';

        return redirect()->route('admin.patients.index')->with('success', $message);
    }

    private function fieldChanged(Patient $patient, string $field, mixed $newValue): bool
    {
        $current = $patient->{$field};

        if ($current instanceof \Illuminate\Support\Carbon) {
            $current = $current->format('Y-m-d');
        }

        return (string) $current !== (string) $newValue;
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
            ->tap(fn ($q) => $this->applyAdvancedFilters($q, $request))
            ->latest()
            ->paginate(20)
            ->appends($request->query());

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

    public function duplicates(Request $request)
    {
        $this->authorize('viewAny', Patient::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();

        $candidates = PatientDuplicateCandidate::query()
            ->when($companyId, fn ($q, $companyId) => $q->where('company_id', $companyId))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')), fn ($q) => $q->where('status', 'pending'))
            ->with(['patientA', 'patientB'])
            ->latest()
            ->paginate(20);

        return view('admin.patients.duplicates', compact('candidates'));
    }

    public function reviewDuplicate(Request $request, PatientDuplicateCandidate $candidate)
    {
        $this->authorize('merge', Patient::class);

        $validated = $request->validate([
            'status' => ['required', 'in:confirmed_duplicate,not_duplicate,needs_investigation,rejected'],
            'review_notes' => ['nullable', 'string'],
        ]);

        $candidate->update([
            'status' => $validated['status'],
            'review_notes' => $validated['review_notes'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $this->auditLogger->log('REVIEW', PatientDuplicateCandidate::class, $candidate->id, null, $candidate->toArray(), $request);

        return redirect()->route('admin.patients.duplicates')->with('success', 'Duplicate candidate reviewed.');
    }

    public function merge(Request $request)
    {
        $this->authorize('merge', Patient::class);

        $masterPatient = Patient::findOrFail($request->input('master_patient_id'));
        $duplicatePatient = Patient::findOrFail($request->input('duplicate_patient_id'));

        // The "merge" ability only checks the permission grant, not company ownership (it takes
        // no Patient instance) — verify both records explicitly so a user with patients.update
        // can't merge records belonging to a company they aren't assigned to.
        $this->authorize('view', $masterPatient);
        $this->authorize('view', $duplicatePatient);

        $oldValues = $duplicatePatient->toArray();

        $this->patientService->mergePatients($masterPatient, $duplicatePatient, $request->user());

        $idA = min($masterPatient->id, $duplicatePatient->id);
        $idB = max($masterPatient->id, $duplicatePatient->id);
        PatientDuplicateCandidate::where('patient_id_a', $idA)->where('patient_id_b', $idB)->update(['status' => 'merged']);

        $this->auditLogger->log('MERGE', Patient::class, $duplicatePatient->id, $oldValues, ['merged_into' => $masterPatient->id], $request);

        return redirect()->route('admin.patients.show', $masterPatient)->with('success', 'Patients merged successfully.');
    }

    public function timeline(Patient $patient)
    {
        $this->authorize('view', $patient);

        $timeline = $this->timeline->for($patient);

        return view('admin.patients.timeline', compact('patient', 'timeline'));
    }

    public function documents(Request $request, Patient $patient)
    {
        $this->authorize('viewDocuments', $patient);

        $documents = $patient->documents()->paginate(20);

        return view('admin.patients.documents', compact('patient', 'documents'));
    }

    public function uploadDocument(Request $request, Patient $patient)
    {
        $this->authorize('uploadDocument', $patient);

        $validated = $request->validate([
            'document' => ['required', 'file', 'max:10240'],
            'document_type' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ]);

        $file = $request->file('document');

        // Enforces the same MIME/extension allow-list and malware-signature scan used by the
        // generic file service, rather than trusting Laravel's "file" rule alone.
        try {
            $this->fileUploadService->validate($file);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['document' => $e->getMessage()]);
        }

        if ($this->fileUploadService->scanForMalware($file)) {
            return back()->withErrors(['document' => 'File appears to contain malicious content.']);
        }

        // Private disk — patient documents must never be reachable via a public URL.
        $path = $file->store('patient-documents');

        $document = PatientDocument::create([
            'company_id' => $patient->company_id,
            'patient_id' => $patient->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'document_type' => $validated['document_type'],
            'description' => $validated['description'] ?? null,
            'uploaded_by' => $request->user()->id,
        ]);

        $this->auditLogger->log('UPLOAD', PatientDocument::class, $document->id, null, $document->toArray(), $request);
        $this->timeline->record($patient, 'DOCUMENT_UPLOADED', "Document uploaded: {$document->file_name}", $document, $request->user());

        return redirect()->route('admin.patients.documents', $patient)->with('success', 'Document uploaded successfully.');
    }

    public function downloadDocument(Request $request, Patient $patient, PatientDocument $document)
    {
        $this->authorize('viewDocuments', $patient);

        abort_unless($document->patient_id === $patient->id, 404);

        $this->auditLogger->log('DOWNLOAD', PatientDocument::class, $document->id, null, null, $request);

        return Storage::disk('local')->download($document->file_path, $document->file_name);
    }

    public function deleteDocument(Patient $patient, PatientDocument $document)
    {
        $this->authorize('uploadDocument', $patient);

        $oldValues = $document->toArray();

        Storage::disk('local')->delete($document->file_path);

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
        $this->timeline->record($patient, 'ALLERGY_ADDED', "Allergy recorded: {$allergy->substance}", $allergy, $request->user());

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

    public function addresses(Patient $patient)
    {
        $this->authorize('view', $patient);

        $addresses = $patient->addresses()->with(['country', 'state'])->get();

        return view('admin.patients.addresses', compact('patient', 'addresses'));
    }

    public function addAddress(Request $request, Patient $patient)
    {
        $this->authorize('update', $patient);

        $validated = $request->validate([
            'address_type' => ['required', 'in:permanent,present,work,mailing'],
            'line1' => ['nullable', 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'state_id' => ['nullable', 'integer', 'exists:states,id'],
            'district' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'is_primary' => ['boolean'],
        ]);

        $address = $this->patientService->addAddress($patient, $validated);

        $this->auditLogger->log('CREATE', \App\Models\PatientAddress::class, $address->id, null, $address->toArray(), $request);

        return redirect()->route('admin.patients.addresses', $patient)->with('success', 'Address added successfully.');
    }

    public function guardians(Patient $patient)
    {
        $this->authorize('view', $patient);

        $guardians = $patient->guardians()->get();

        return view('admin.patients.guardians', compact('patient', 'guardians'));
    }

    public function addGuardian(Request $request, Patient $patient)
    {
        $this->authorize('update', $patient);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'relationship' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'national_identifier' => ['nullable', 'string', 'max:255'],
            'is_primary' => ['boolean'],
        ]);

        $guardian = $this->patientService->addGuardian($patient, $validated);

        $this->auditLogger->log('CREATE', \App\Models\PatientGuardian::class, $guardian->id, null, $guardian->toArray(), $request);

        return redirect()->route('admin.patients.guardians', $patient)->with('success', 'Guardian added successfully.');
    }

    public function setPreferences(Request $request, Patient $patient)
    {
        $this->authorize('update', $patient);

        $validated = $request->validate([
            'preferred_language' => ['nullable', 'string', 'max:10'],
            'preferred_contact_method' => ['nullable', 'in:phone,email,sms'],
            'preferred_notification_channel' => ['nullable', 'in:sms,email,push,none'],
            'accessibility_requirements' => ['nullable', 'string'],
        ]);

        $this->patientService->setPreferences($patient, $validated);

        return redirect()->route('admin.patients.show', $patient)->with('success', 'Preferences updated successfully.');
    }

    public function uploadPhoto(Request $request, Patient $patient)
    {
        $this->authorize('update', $patient);

        $validated = $request->validate([
            'photo' => ['required', 'image', 'max:5120'],
        ]);

        $file = $this->fileService->store($validated['photo'], $patient, $request->user());

        $patient->update(['photo_file_id' => $file->id]);

        $this->auditLogger->log('UPDATE', Patient::class, $patient->id, null, ['photo_file_id' => $file->id], $request);

        return redirect()->route('admin.patients.show', $patient)->with('success', 'Photo updated successfully.');
    }

    public function consents(Patient $patient)
    {
        $this->authorize('view', $patient);

        $consents = $patient->consents()->with('grantedBy')->latest()->get();

        return view('admin.patients.consents', compact('patient', 'consents'));
    }

    public function grantConsent(Request $request, Patient $patient)
    {
        $this->authorize('manageConsents', $patient);

        $validated = $request->validate([
            'consent_type' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $consent = $this->patientService->grantConsent($patient, $validated['consent_type'], $request->user(), $validated['notes'] ?? null);

        $this->auditLogger->log('CREATE', PatientConsent::class, $consent->id, null, $consent->toArray(), $request);

        return redirect()->route('admin.patients.consents', $patient)->with('success', 'Consent recorded successfully.');
    }

    public function withdrawConsent(Request $request, Patient $patient, PatientConsent $consent)
    {
        $this->authorize('manageConsents', $patient);

        abort_unless($consent->patient_id === $patient->id, 404);

        $this->patientService->withdrawConsent($consent, $request->user());

        $this->auditLogger->log('UPDATE', PatientConsent::class, $consent->id, null, ['status' => 'withdrawn'], $request);

        return redirect()->route('admin.patients.consents', $patient)->with('success', 'Consent withdrawn.');
    }

    public function amendments(Request $request)
    {
        $this->authorize('approveAmendment', Patient::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();

        $amendments = PatientAmendment::query()
            ->when($companyId, fn ($q, $companyId) => $q->where('company_id', $companyId))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')), fn ($q) => $q->whereIn('status', ['submitted', 'pending_approval']))
            ->with(['patient', 'requestedBy'])
            ->latest()
            ->paginate(20);

        return view('admin.patients.amendments', compact('amendments'));
    }

    public function requestAmendment(Request $request, Patient $patient)
    {
        $this->authorize('requestAmendment', $patient);

        $validated = $request->validate([
            'field' => ['required', 'string'],
            'value' => ['nullable', 'string'],
            'reason' => ['required', 'string'],
        ]);

        $amendment = $this->amendmentService->request(
            $patient,
            [$validated['field'] => $validated['value']],
            $validated['reason'],
            $request->user(),
        );

        return redirect()->route('admin.patients.show', $patient)->with('success', "Amendment request #{$amendment->id} submitted for approval.");
    }

    public function approveAmendment(Request $request, PatientAmendment $amendment)
    {
        $this->authorize('approveAmendment', Patient::class);

        $this->amendmentService->approve($amendment, $request->user(), $request->input('note'));

        return redirect()->route('admin.patients.amendments')->with('success', 'Amendment approved and applied.');
    }

    public function rejectAmendment(Request $request, PatientAmendment $amendment)
    {
        $this->authorize('approveAmendment', Patient::class);

        $validated = $request->validate(['reason' => ['required', 'string']]);

        $this->amendmentService->reject($amendment, $request->user(), $validated['reason']);

        return redirect()->route('admin.patients.amendments')->with('success', 'Amendment rejected.');
    }

    public function auditTrail(Patient $patient)
    {
        $this->authorize('view', $patient);

        $logs = AuditLog::query()
            ->where('model_type', Patient::class)
            ->where('model_id', $patient->id)
            ->latest()
            ->paginate(20);

        return view('admin.patients.audit', compact('patient', 'logs'));
    }

    /**
     * Patient Appointment History (spec §45) — reads the existing Phase 2 Appointment model
     * directly rather than duplicating appointment data onto the patient record.
     */
    public function appointments(Patient $patient)
    {
        $this->authorize('view', $patient);

        $appointments = \App\Models\Appointment::where('patient_id', $patient->id)
            ->with(['provider', 'doctor', 'appointmentType'])
            ->latest('appointment_date')
            ->paginate(20);

        return view('admin.patients.appointments', compact('patient', 'appointments'));
    }

    public function print(Patient $patient)
    {
        $this->authorize('print', $patient);

        $patient->load(['addresses', 'contacts', 'allergies']);

        return view('admin.patients.print', compact('patient'));
    }

    public function barcode(Patient $patient)
    {
        $this->authorize('print', $patient);

        return response($this->barcodeService->svg($patient))->header('Content-Type', 'image/svg+xml');
    }

    public function fhir(Patient $patient)
    {
        $this->authorize('view', $patient);

        $patient->load(['identifiers', 'addresses.country', 'addresses.state', 'guardians', 'maritalStatus']);

        return response()->json($this->fhirMapper->map($patient));
    }

    public function enablePortal(Request $request, Patient $patient)
    {
        $this->authorize('update', $patient);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $account = \App\Models\PatientPortalAccount::updateOrCreate(
            ['patient_id' => $patient->id],
            ['company_id' => $patient->company_id, 'email' => $validated['email'], 'is_active' => false, 'invited_at' => now()],
        );

        $patient->update(['portal_enabled' => true]);

        $this->auditLogger->log('CREATE', \App\Models\PatientPortalAccount::class, $account->id, null, $account->toArray(), $request);

        return redirect()->route('admin.patients.show', $patient)->with('success', 'Patient portal invitation created.');
    }
}
