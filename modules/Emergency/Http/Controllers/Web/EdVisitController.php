<?php

namespace Modules\Emergency\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\ClinicalOrder;
use App\Models\Ed\EdBay;
use App\Models\Ed\EdMedicationAdministration;
use App\Models\Ed\EdVisit;
use App\Models\EncounterProcedure;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Ed\EdBayService;
use App\Services\Ed\EdTriageService;
use App\Services\Ed\EdVisitService;
use App\Services\Ed\TriageScale;
use App\Services\TenantContextResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EdVisitController extends Controller
{
    public function __construct(
        private readonly EdVisitService $visits,
        private readonly EdTriageService $triage,
        private readonly EdBayService $bays,
        private readonly TriageScale $scale,
        private readonly TenantContextResolver $tenant,
        private readonly AuditLogger $audit,
    ) {}

    public function create(Request $request)
    {
        abort_unless($request->user()->can('ed.visit.create'), 403);

        return view('admin.ed.arrival', ['modes' => config('ed.arrival_modes')]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('ed.visit.create'), 403);

        $validated = $request->validate([
            'patient_public_id' => ['nullable', 'string', 'size:26'],
            'arrival_mode' => ['required', 'in:'.implode(',', config('ed.arrival_modes'))],
            'chief_complaint' => ['nullable', 'string', 'max:500'],
            'sex' => ['nullable', 'in:M,F,O'],
            'estimated_age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_mass_casualty' => ['sometimes', 'boolean'],
        ]);

        $organizationId = $this->tenant->getOrganizationId();
        $patientId = filled($validated['patient_public_id'] ?? null)
            ? Patient::query()->where('organization_id', $organizationId)->where('public_id', $validated['patient_public_id'])->firstOrFail()->id
            : null;

        $visit = $this->visits->arrive($request->user(), Branch::query()->where('organization_id', $organizationId)->findOrFail($this->tenant->getBranchId()), $validated + ['patient_id' => $patientId]);
        $this->audit->log('ED_ARRIVAL', EdVisit::class, $visit->id, null, ['visit' => $visit->visit_number, 'unknown' => $visit->is_unknown_patient], $request);

        return redirect()->route('admin.ed.visits.show', $visit)->with('success', __('Patient registered in the emergency department.'));
    }

    public function show(Request $request, EdVisit $visit)
    {
        $this->authorizeVisit($request, $visit, 'ed.visit.view');

        return view('admin.ed.visit', [
            'visit' => $visit->load(['patient', 'bay.zone', 'zone', 'doctor', 'nurse', 'triageRecords.assessor', 'statusHistory', 'resusEvents.recorder']),
            'levels' => $this->scale->levels(),
            'orders' => ClinicalOrder::query()->where('encounter_id', $visit->encounter_id)->with('items')->latest('ordered_at')->get(),
            'prescriptions' => Prescription::query()->where('encounter_id', $visit->encounter_id)->with('items')->latest('prescribed_at')->get(),
            'procedures' => EncounterProcedure::query()->where('encounter_id', $visit->encounter_id)->latest('recorded_at')->get(),
            'medications' => EdMedicationAdministration::query()->where('visit_id', $visit->id)->with(['giver', 'orderer'])->latest('given_at')->get(),
            'doctors' => User::query()->where('is_active', true)->whereHas('organizations', fn ($q) => $q->where('organizations.id', $visit->organization_id))->whereHas('roles', fn ($q) => $q->where('name', 'doctor'))->orderBy('name')->get(['id', 'name']),
            'freeBays' => EdBay::query()->where('organization_id', $visit->organization_id)->where('status', EdBay::AVAILABLE)->where('is_active', true)->with('zone')->orderBy('zone_id')->orderBy('code')->get(),
        ]);
    }

    public function triage(Request $request, EdVisit $visit): RedirectResponse
    {
        $this->authorizeVisit($request, $visit, 'ed.triage.perform');

        $validated = $request->validate([
            'level' => ['required', 'integer', 'in:'.implode(',', array_keys($this->scale->levels()))],
            'complaint' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'vitals' => ['nullable', 'array'],
            'vitals.*' => ['nullable', 'numeric'],
        ]);

        $this->triage->triage($visit, $request->user(), $validated);

        return back()->with('success', __('Triage recorded.'));
    }

    public function assignBay(Request $request, EdVisit $visit): RedirectResponse
    {
        $this->authorizeVisit($request, $visit, 'ed.visit.update');
        $validated = $request->validate(['bay' => ['required', 'string', 'size:26']]);

        $this->bays->assign($visit, EdBay::query()->where('organization_id', $visit->organization_id)->where('public_id', $validated['bay'])->firstOrFail(), $request->user());

        return back()->with('success', __('Bay assigned.'));
    }

    public function releaseBay(Request $request, EdVisit $visit): RedirectResponse
    {
        $this->authorizeVisit($request, $visit, 'ed.visit.update');
        $this->bays->release($visit, $request->user());

        return back()->with('success', __('Bay released.'));
    }

    public function startTreatment(Request $request, EdVisit $visit): RedirectResponse
    {
        $this->authorizeVisit($request, $visit, 'ed.visit.update');
        $this->visits->startTreatment($visit, $request->user());

        return back()->with('success', __('Treatment started.'));
    }

    public function team(Request $request, EdVisit $visit): RedirectResponse
    {
        $this->authorizeVisit($request, $visit, 'ed.visit.update');
        $validated = $request->validate(['doctor_id' => ['nullable', 'integer'], 'nurse_id' => ['nullable', 'integer']]);
        $this->visits->assignTeam($visit, $validated['doctor_id'] ?? null, $validated['nurse_id'] ?? null);

        return back()->with('success', __('Team updated.'));
    }

    /** A visit of another organization or branch does not exist for this caller (404). */
    private function authorizeVisit(Request $request, EdVisit $visit, string $permission): void
    {
        abort_unless($visit->organization_id === $this->tenant->getOrganizationId(), 404);
        abort_unless(! $this->tenant->getBranchId() || $visit->branch_id === $this->tenant->getBranchId(), 404);
        abort_unless($request->user()->can($permission), 403);
    }
}
