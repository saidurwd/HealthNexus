<?php

namespace Modules\BloodBank\Http\Controllers\Web;

use App\Models\Blood\BloodIssue;
use App\Models\Blood\BloodTransfusion;
use App\Models\Clinical\ClinicalObservation;
use App\Models\User;
use App\Services\Blood\BloodPolicy;
use App\Services\Blood\BloodTransfusionService;
use App\Services\TenantContextResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class BloodTransfusionController extends BloodController
{
    public function __construct(TenantContextResolver $tenant, private readonly BloodTransfusionService $transfusions, private readonly BloodPolicy $policy)
    {
        parent::__construct($tenant);
    }

    /** Units on the ward waiting to be started, and the transfusions running. */
    public function index(Request $request)
    {
        $this->allow($request, 'transfusion.view');
        $org = $this->organizationId();

        return view('admin.blood.transfusions.index', [
            'waiting' => BloodIssue::query()->with(['unit', 'patient:id,public_id,first_name,last_name,enterprise_patient_no'])->where('organization_id', $org)->where('status', 'issued')->orderBy('issued_at')->get(),
            'running' => BloodTransfusion::query()->where('organization_id', $org)->where('status', 'in_progress')->orderBy('started_at')->get()->each(fn ($t) => $t->setRelation('issue', BloodIssue::with(['unit', 'patient'])->find($t->issue_id))),
        ]);
    }

    public function start(Request $request, BloodIssue $issue): RedirectResponse
    {
        $this->scoped($request, $issue, 'transfusion.record');
        $validated = $request->validate([
            'wristband_scan' => ['required', 'string', 'max:60'], 'bag_scan' => ['required', 'string', 'max:60'], 'second_checker_email' => ['required', 'email'], 'second_checker_password' => ['required', 'string'],
            'override_reason' => ['nullable', 'string', 'max:300'], 'pulse' => ['nullable', 'numeric'], 'temperature' => ['nullable', 'numeric'], 'systolic' => ['nullable', 'numeric'], 'diastolic' => ['nullable', 'numeric'],
        ]);

        // The second person proves who they are at the bedside: their own login, not a tick box.
        $second = User::query()->where('email', $validated['second_checker_email'])->where('is_active', true)->first();
        if (! $second || ! Hash::check($validated['second_checker_password'], $second->password) || ! $second->organizations()->where('organizations.id', $this->organizationId())->exists()) {
            throw ValidationException::withMessages(['second_checker_email' => 'The second checker could not be confirmed.']);
        }

        $transfusion = $this->transfusions->start($issue, $request->user(), $second, $validated['wristband_scan'], $validated['bag_scan'], $validated['override_reason'] ?? null, $request->only(['pulse', 'temperature', 'systolic', 'diastolic']));

        return redirect()->route('admin.blood.transfusions.show', $transfusion)->with('success', __('Transfusion started. Check the patient as shown.'));
    }

    public function show(Request $request, BloodTransfusion $transfusion)
    {
        $this->scoped($request, $transfusion, 'transfusion.view');
        $issue = BloodIssue::with(['unit', 'patient'])->findOrFail($transfusion->issue_id);

        return view('admin.blood.transfusions.show', [
            'transfusion' => $transfusion->load('reactions'), 'issue' => $issue,
            'observations' => ClinicalObservation::query()->where('context_type', 'transfusion')->where('context_id', $transfusion->id)->orderBy('effective_at')->get(),
            'reactionTypes' => $this->policy->get('reaction_types'), 'severities' => $this->policy->get('reaction_severities'), 'schedule' => $this->policy->get('transfusion')['observation_minutes'],
        ]);
    }

    public function observe(Request $request, BloodTransfusion $transfusion): RedirectResponse
    {
        $this->scoped($request, $transfusion, 'transfusion.record');
        $validated = $request->validate(['pulse' => ['nullable', 'numeric'], 'temperature' => ['nullable', 'numeric'], 'respiratory_rate' => ['nullable', 'numeric'], 'oxygen_saturation' => ['nullable', 'numeric'], 'systolic' => ['nullable', 'numeric'], 'diastolic' => ['nullable', 'numeric']]);
        $this->transfusions->recordObservations($transfusion, $request->user(), $validated);

        return back()->with('success', __('Observations recorded.'));
    }

    public function complete(Request $request, BloodTransfusion $transfusion): RedirectResponse
    {
        $this->scoped($request, $transfusion, 'transfusion.record');
        $validated = $request->validate(['volume_given_ml' => ['required', 'integer', 'between:1,1000']]);
        $this->transfusions->complete($transfusion, $request->user(), (int) $validated['volume_given_ml']);

        return redirect()->route('admin.blood.transfusions.index')->with('success', __('Transfusion completed.'));
    }

    public function reaction(Request $request, BloodTransfusion $transfusion): RedirectResponse
    {
        $this->scoped($request, $transfusion, 'reaction.record');
        $validated = $request->validate(['reaction_type' => ['required', 'string'], 'severity' => ['required', 'string'], 'symptoms' => ['nullable', 'string', 'max:500'], 'actions_taken' => ['nullable', 'string', 'max:500']]);
        $this->transfusions->reportReaction($transfusion, $request->user(), $validated['reaction_type'], $validated['severity'], array_filter(array_map('trim', explode(',', (string) ($validated['symptoms'] ?? '')))), $validated['actions_taken'] ?? null);

        return back()->with('warning', __('Reaction recorded. The transfusion is stopped and the blood bank and doctor have been alerted.'));
    }
}
