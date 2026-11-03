<?php

namespace Modules\Ot\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ot\OtSurgery;
use App\Models\Ot\OtSurgeryRequest;
use App\Services\AuditLogger;
use App\Services\Ot\OtReceptionService;
use App\Services\Ot\OtSchedulingService;
use App\Services\Ot\OtSurgeryService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class OtSurgeryController extends Controller
{
    public function __construct(
        private readonly OtSurgeryService $surgeries,
        private readonly OtSchedulingService $scheduling,
        private readonly OtReceptionService $reception,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', OtSurgery::class);

        $organizationId = app(TenantContextResolver::class)->getOrganizationId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $surgeries = OtSurgery::forTenant($organizationId, $branchId)
            ->with(['patient', 'theatre', 'primarySurgeon', 'primaryProcedure'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('scheduled_date', $request->input('date')))
            ->latest('scheduled_date')
            ->paginate(20)
            ->withQueryString();

        return view('admin.ot.surgeries.index', compact('surgeries'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', OtSurgery::class);

        $surgeryRequest = $request->filled('surgery_request_id')
            ? OtSurgeryRequest::where('status', OtSurgeryRequest::STATUS_APPROVED)->findOrFail($request->input('surgery_request_id'))
            : null;

        return view('admin.ot.surgeries.create', compact('surgeryRequest'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', OtSurgery::class);

        $validated = $request->validate([
            'surgery_request_id' => ['nullable', 'integer', 'exists:ot_surgery_requests,id'],
            'patient_id' => ['required_without:surgery_request_id', 'integer', 'exists:patients,id'],
            'encounter_id' => ['required_without:surgery_request_id', 'integer', 'exists:encounters,id'],
            'admission_id' => ['nullable', 'integer', 'exists:ipd_admissions,id'],
            'primary_procedure_id' => ['nullable', 'integer', 'exists:ot_procedures,id'],
            'specialty_id' => ['nullable', 'integer', 'exists:specialties,id'],
            'priority' => ['nullable', 'string'],
            'surgery_type' => ['nullable', 'string', 'in:elective,emergency'],
            'site' => ['nullable', 'string', 'max:255'],
            'laterality' => ['nullable', 'string', 'max:50'],
        ]);

        try {
            if (! empty($validated['surgery_request_id'])) {
                $surgeryRequest = OtSurgeryRequest::findOrFail($validated['surgery_request_id']);
                unset($validated['surgery_request_id']);
                $surgery = $this->surgeries->createFromRequest($surgeryRequest, $request->user(), $validated);
            } else {
                $surgery = $this->surgeries->create($validated, $request->user());
            }
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        $this->auditLogger->log('CREATE', OtSurgery::class, $surgery->id, null, $surgery->toArray(), $request);

        return redirect()->route('admin.ot.surgeries.show', $surgery)->with('success', __('Surgical case created.'));
    }

    public function show(OtSurgery $surgery)
    {
        $this->authorize('view', $surgery);

        $surgery->load([
            'patient', 'encounter', 'admission', 'surgeryRequest', 'primaryProcedure', 'specialty',
            'theatre', 'primarySurgeon', 'assistantSurgeon', 'anesthetist', 'consent',
            'procedures.procedure', 'currentTeam.provider', 'scheduleEvents', 'preoperativeAssessment',
            'preoperativeChecklist.items', 'safetyChecklists.items', 'operativeNote.amendments',
            'complications', 'counts', 'specimens', 'implants', 'recoveryRecord',
        ]);

        return view('admin.ot.surgeries.show', compact('surgery'));
    }

    public function schedule(Request $request, OtSurgery $surgery)
    {
        $this->authorize('schedule', $surgery);

        $validated = $request->validate([
            'theatre_id' => ['required', 'integer', 'exists:ot_theatres,id'],
            'scheduled_date' => ['nullable', 'date'],
            'scheduled_start_at' => ['required', 'date'],
            'scheduled_end_at' => ['required', 'date', 'after:scheduled_start_at'],
            'primary_surgeon_id' => ['nullable', 'integer', 'exists:providers,id'],
            'assistant_surgeon_id' => ['nullable', 'integer', 'exists:providers,id'],
            'anesthetist_id' => ['nullable', 'integer', 'exists:providers,id'],
        ]);

        $validated['scheduled_start_at'] = Carbon::parse($validated['scheduled_start_at']);
        $validated['scheduled_end_at'] = Carbon::parse($validated['scheduled_end_at']);

        try {
            $this->scheduling->schedule($surgery, $validated, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('SCHEDULE', OtSurgery::class, $surgery->id, null, $validated, $request);

        return back()->with('success', __('Surgery scheduled.'));
    }

    public function reschedule(Request $request, OtSurgery $surgery)
    {
        $this->authorize('schedule', $surgery);

        $validated = $request->validate([
            'theatre_id' => ['nullable', 'integer', 'exists:ot_theatres,id'],
            'scheduled_start_at' => ['required', 'date'],
            'scheduled_end_at' => ['required', 'date', 'after:scheduled_start_at'],
            'reason' => ['nullable', 'string'],
        ]);

        $validated['scheduled_start_at'] = Carbon::parse($validated['scheduled_start_at']);
        $validated['scheduled_end_at'] = Carbon::parse($validated['scheduled_end_at']);

        try {
            $this->scheduling->reschedule($surgery, $validated, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', __('Surgery rescheduled.'));
    }

    public function postpone(Request $request, OtSurgery $surgery)
    {
        $this->authorize('postpone', $surgery);

        $validated = $request->validate(['reason' => ['required', 'string']]);

        try {
            $this->scheduling->postpone($surgery, $validated['reason'], $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', __('Surgery postponed.'));
    }

    public function cancel(Request $request, OtSurgery $surgery)
    {
        $this->authorize('cancel', $surgery);

        $validated = $request->validate(['reason' => ['required', 'string']]);

        try {
            $this->scheduling->cancel($surgery, $validated['reason'], $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('CANCEL', OtSurgery::class, $surgery->id, null, $validated, $request);

        return back()->with('success', __('Surgery cancelled.'));
    }

    public function receive(Request $request, OtSurgery $surgery)
    {
        $this->authorize('update', $surgery);

        try {
            $this->reception->receive($surgery, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', __('Patient received into theatre.'));
    }

    public function start(Request $request, OtSurgery $surgery)
    {
        $this->authorize('start', $surgery);

        try {
            $this->reception->start($surgery, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('START', OtSurgery::class, $surgery->id, null, ['status' => 'in_procedure'], $request);

        return back()->with('success', __('Procedure started.'));
    }

    public function complete(Request $request, OtSurgery $surgery)
    {
        $this->authorize('complete', $surgery);

        try {
            $this->reception->complete($surgery, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('COMPLETE', OtSurgery::class, $surgery->id, null, ['status' => 'procedure_completed'], $request);

        return back()->with('success', __('Procedure marked complete.'));
    }
}
