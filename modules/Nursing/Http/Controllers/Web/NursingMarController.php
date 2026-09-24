<?php

namespace Modules\Nursing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Nursing\NursingEpisode;
use App\Models\Nursing\NursingMedicationAdministration;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Nursing\MedicationAdministrationService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class NursingMarController extends Controller
{
    public function __construct(
        private readonly MedicationAdministrationService $mar,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', NursingMedicationAdministration::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $mar = NursingMedicationAdministration::forTenant($companyId, $branchId)
            ->with(['patient', 'admission', 'medication'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->input('episode_id'), fn ($q) => $q->where('episode_id', $request->input('episode_id')))
            ->orderBy('scheduled_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.nursing.mar.index', compact('mar'));
    }

    public function show(NursingMedicationAdministration $mar)
    {
        $this->authorize('view', $mar);

        $mar->load(['patient', 'admission', 'medication', 'batch', 'dispensingItem', 'administeredBy', 'witnessedBy', 'corrections']);

        return view('admin.nursing.mar.show', compact('mar'));
    }

    public function storeAdHoc(Request $request, NursingEpisode $episode)
    {
        $this->authorize('create', NursingMedicationAdministration::class);
        Gate::authorize('nursing.mar.administer_without_dispensing');

        $validated = $request->validate([
            'medication_id' => ['nullable', 'integer', 'exists:pharmacy_medications,id'],
            'dose' => ['required', 'string'],
            'dose_unit' => ['nullable', 'string'],
            'route' => ['required', 'string'],
            'site' => ['nullable', 'string'],
            'scheduled_at' => ['nullable', 'date'],
            'is_prn' => ['nullable', 'boolean'],
            'prn_reason' => ['nullable', 'string'],
        ]);

        $mar = $this->mar->createAdHoc($episode, $validated, $request->user());

        $this->auditLogger->log('CREATE', NursingMedicationAdministration::class, $mar->id, null, $mar->toArray(), $request);

        return back()->with('success', 'Ad-hoc medication administration record created.');
    }

    public function administer(Request $request, NursingMedicationAdministration $mar)
    {
        $this->authorize('administer', $mar);

        $validated = $request->validate([
            'dose' => ['nullable', 'string'],
            'route' => ['nullable', 'string'],
            'site' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'witness_id' => ['nullable', 'integer', 'exists:users,id'],
            'safety_checks' => ['required', 'array'],
        ]);

        $witness = ! empty($validated['witness_id']) ? User::find($validated['witness_id']) : null;

        try {
            $updated = $this->mar->administer($mar, $request->user(), $validated['safety_checks'], $validated, $witness);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('ADMINISTER', NursingMedicationAdministration::class, $updated->id, null, ['status' => 'administered'], $request);

        return back()->with('success', 'Medication administered.');
    }

    public function hold(Request $request, NursingMedicationAdministration $mar)
    {
        $this->authorize('hold', $mar);

        $validated = $request->validate(['reason' => ['required', 'string']]);

        try {
            $this->mar->hold($mar, $request->user(), $validated['reason']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('HOLD', NursingMedicationAdministration::class, $mar->id, null, $validated, $request);

        return back()->with('success', 'Medication held.');
    }

    public function refuse(Request $request, NursingMedicationAdministration $mar)
    {
        $this->authorize('refuse', $mar);

        $validated = $request->validate(['reason' => ['required', 'string']]);

        try {
            $this->mar->refuse($mar, $request->user(), $validated['reason']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('REFUSE', NursingMedicationAdministration::class, $mar->id, null, $validated, $request);

        return back()->with('success', 'Medication refusal recorded.');
    }

    public function omit(Request $request, NursingMedicationAdministration $mar)
    {
        $this->authorize('omit', $mar);

        $validated = $request->validate(['reason' => ['required', 'string']]);

        try {
            $this->mar->omit($mar, $request->user(), $validated['reason']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('OMIT', NursingMedicationAdministration::class, $mar->id, null, $validated, $request);

        return back()->with('success', 'Medication omission recorded.');
    }

    public function correct(Request $request, NursingMedicationAdministration $mar)
    {
        $this->authorize('correct', $mar);

        $validated = $request->validate([
            'correction_type' => ['nullable', 'string'],
            'reason' => ['required', 'string'],
        ]);

        try {
            $correction = $this->mar->correct($mar, $validated, $validated['reason'], $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('CORRECT', NursingMedicationAdministration::class, $mar->id, null, $correction->toArray(), $request);

        return back()->with('success', 'Correction recorded.');
    }
}
