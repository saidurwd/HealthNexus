<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StorePatientRequest;
use App\Http\Requests\Api\UpdatePatientRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Patient;
use App\Models\PatientDuplicateCandidate;
use App\Services\AuditLogger;
use App\Services\FileUploadService;
use App\Services\Patients\PatientDuplicateScoringService;
use App\Services\Patients\PatientService;
use App\Services\Patients\PatientTimelineService;
use App\Services\TenantContextResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function __construct(
        private AuditLogger $auditLogger,
        private PatientService $patientService,
        private PatientTimelineService $timeline,
        private PatientDuplicateScoringService $duplicateScoring,
        private FileUploadService $fileUploadService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $patients = Patient::query()
            ->when(! $user->hasRole('super_admin'), fn ($q) => $q->whereIn('company_id', $user->companies()->pluck('companies.id')))
            ->when($request->filled('search'), fn ($q, $search) => $q->where(function ($q2) use ($search) {
                $q2->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('enterprise_patient_no', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }))
            ->get();

        return ApiResponse::success($patients);
    }

    public function search(Request $request): JsonResponse
    {
        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $search = $request->input('q', '');

        $patients = Patient::query()
            ->when($companyId, fn ($q, $companyId) => $q->where('company_id', $companyId))
            ->when($search, fn ($q, $search) => $q->where(function ($q2) use ($search) {
                $q2->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('enterprise_patient_no', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('national_identifier', 'like', "%{$search}%");
            }))
            ->paginate(20);

        return ApiResponse::paginated($patients);
    }

    /**
     * Routed through PatientService (MRN generation, optional branch registration, nested
     * identifiers/contacts) rather than a bare Patient::create() — the previous implementation
     * bypassed all of that, silently producing a patient with no enterprise_patient_no.
     */
    public function store(StorePatientRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = $request->user();

        abort_if(
            ! $user->hasRole('super_admin') && ! $user->companies()->where('companies.id', $validated['company_id'])->exists(),
            403,
            'You do not have access to this company.'
        );

        $branch = ! empty($validated['branch_id']) ? \App\Models\Branch::findOrFail($validated['branch_id']) : null;

        $patient = $this->patientService->createPatient($validated, $user, $branch);

        $this->auditLogger->log('CREATE', Patient::class, $patient->id, null, $patient->toArray(), $request);

        return ApiResponse::success($patient, 'Patient created successfully', 201);
    }

    public function show(Request $request, Patient $patient): JsonResponse
    {
        $this->authorize('view', $patient);

        return ApiResponse::success($patient);
    }

    public function update(UpdatePatientRequest $request, Patient $patient): JsonResponse
    {
        $this->authorize('update', $patient);

        $validated = $request->validated();

        $patient->update($validated);

        $this->auditLogger->log('UPDATE', Patient::class, $patient->id, $patient->getOriginal(), $patient->toArray(), $request);

        return ApiResponse::success($patient, 'Patient updated successfully');
    }

    public function destroy(Request $request, Patient $patient): JsonResponse
    {
        $this->authorize('delete', $patient);

        $oldValues = $patient->toArray();

        $patient->delete();

        $this->auditLogger->log('DELETE', Patient::class, $patient->id, $oldValues, null, $request);

        return ApiResponse::success(null, 'Patient deleted successfully');
    }

    public function identifiers(Patient $patient): JsonResponse
    {
        $this->authorize('view', $patient);

        return ApiResponse::success($patient->identifiers);
    }

    public function storeIdentifier(Request $request, Patient $patient): JsonResponse
    {
        $this->authorize('update', $patient);

        $validated = $request->validate([
            'identifier_type' => ['required', 'string', 'max:255'],
            'identifier_value' => ['required', 'string', 'max:255'],
            'issuing_authority' => ['nullable', 'string', 'max:255'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'is_primary' => ['boolean'],
        ]);

        $this->patientService->addIdentifier($patient, array_merge($validated, ['company_id' => $patient->company_id]));

        return ApiResponse::success($patient->identifiers()->latest()->first(), 'Identifier added successfully', 201);
    }

    public function contacts(Patient $patient): JsonResponse
    {
        $this->authorize('view', $patient);

        return ApiResponse::success($patient->contacts);
    }

    public function storeContact(Request $request, Patient $patient): JsonResponse
    {
        $this->authorize('update', $patient);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'relationship' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'is_emergency' => ['boolean'],
        ]);

        $contact = $this->patientService->addContact($patient, $validated);

        return ApiResponse::success($contact, 'Contact added successfully', 201);
    }

    public function addresses(Patient $patient): JsonResponse
    {
        $this->authorize('view', $patient);

        return ApiResponse::success($patient->addresses);
    }

    public function storeAddress(Request $request, Patient $patient): JsonResponse
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

        return ApiResponse::success($address, 'Address added successfully', 201);
    }

    public function documents(Patient $patient): JsonResponse
    {
        $this->authorize('viewDocuments', $patient);

        return ApiResponse::success($patient->documents);
    }

    public function storeDocument(Request $request, Patient $patient): JsonResponse
    {
        $this->authorize('uploadDocument', $patient);

        $validated = $request->validate([
            'document' => ['required', 'file', 'max:10240'],
            'document_type' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ]);

        $file = $request->file('document');

        try {
            $this->fileUploadService->validate($file);
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::validationError(['document' => [$e->getMessage()]]);
        }

        if ($this->fileUploadService->scanForMalware($file)) {
            return ApiResponse::validationError(['document' => ['File appears to contain malicious content.']]);
        }

        $path = $file->store('patient-documents');

        $document = \App\Models\PatientDocument::create([
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

        return ApiResponse::success($document, 'Document uploaded successfully', 201);
    }

    public function timeline(Patient $patient): JsonResponse
    {
        $this->authorize('view', $patient);

        return ApiResponse::success($this->timeline->for($patient));
    }

    public function duplicateCheck(Request $request): JsonResponse
    {
        $companyId = app(TenantContextResolver::class)->getCompanyId();

        $duplicates = $this->patientService->detectDuplicates(
            $companyId,
            $request->input('first_name', ''),
            $request->input('last_name', ''),
            $request->input('phone'),
            $request->input('national_identifier'),
            $request->input('email'),
        );

        return ApiResponse::success($duplicates);
    }

    public function duplicates(Request $request): JsonResponse
    {
        $companyId = app(TenantContextResolver::class)->getCompanyId();

        $candidates = PatientDuplicateCandidate::query()
            ->when($companyId, fn ($q, $companyId) => $q->where('company_id', $companyId))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')), fn ($q) => $q->where('status', 'pending'))
            ->with(['patientA', 'patientB'])
            ->paginate(20);

        return ApiResponse::paginated($candidates);
    }

    public function merge(Request $request): JsonResponse
    {
        $master = Patient::findOrFail($request->input('master_patient_id'));
        $duplicate = Patient::findOrFail($request->input('duplicate_patient_id'));

        $this->authorize('merge', Patient::class);
        $this->authorize('view', $master);
        $this->authorize('view', $duplicate);

        $this->patientService->mergePatients($master, $duplicate, $request->user());

        return ApiResponse::success($master->fresh(), 'Patients merged successfully');
    }
}
