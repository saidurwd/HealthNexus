<?php

namespace Modules\Portal\Http\Controllers\Web;

use App\Models\Crm\CrmFeedback;
use App\Models\Crm\CrmInteraction;
use App\Models\Patient;
use App\Models\User;
use App\Services\Crm\FeedbackService;
use App\Services\Crm\InteractionService;
use App\Services\Portal\PortalReportService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

/** CRM: the dashboard, the contact log and the ratings (S11). */
class CrmDeskController extends PortalStaffController
{
    public function __construct(TenantContextResolver $tenant, private readonly PortalReportService $reports, private readonly InteractionService $interactions, private readonly FeedbackService $feedback)
    {
        parent::__construct($tenant);
    }

    public function dashboard(Request $request)
    {
        $this->allow($request, 'crm.dashboard.view');

        return view('admin.crm.dashboard', ['d' => $this->reports->crm($this->organizationId())]);
    }

    public function interactions(Request $request)
    {
        $this->allow($request, 'crm.interaction.view');
        $status = $request->query('status', 'open');
        $rows = CrmInteraction::query()->with(['patient:id,public_id,first_name,last_name,enterprise_patient_no', 'assignee:id,name'])->where('organization_id', $this->organizationId())
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))->when($request->boolean('mine'), fn ($q) => $q->where('assigned_to', $request->user()->id))->orderBy('follow_up_on')->latest('id')->paginate(25)->withQueryString();

        return view('admin.crm.interactions', ['rows' => $rows, 'status' => $status, 'staff' => $this->staff()]);
    }

    public function storeInteraction(Request $request)
    {
        $this->allow($request, 'crm.interaction.manage');
        $d = $request->validate(['patient_no' => ['nullable', 'string', 'max:60'], 'contact_name' => ['nullable', 'string', 'max:120'], 'contact_phone' => ['nullable', 'string', 'max:20'], 'channel' => ['required', 'string', 'max:10'], 'direction' => ['nullable', 'in:in,out'],
            'subject' => ['required', 'string', 'max:160'], 'summary' => ['nullable', 'string', 'max:3000'], 'outcome' => ['nullable', 'string', 'max:160'], 'follow_up_on' => ['nullable', 'date', 'after_or_equal:today'], 'assigned_to' => ['nullable', 'integer', 'exists:users,id']]);
        $patient = ! empty($d['patient_no']) ? Patient::query()->where('organization_id', $this->organizationId())->where('enterprise_patient_no', trim($d['patient_no']))->first() : null;
        if (! empty($d['patient_no']) && $patient === null) {
            return back()->withInput()->withErrors(['patient_no' => __('No patient has that number.')]);
        }
        $this->interactions->log($this->organizationId(), $request->user(), ['patient_id' => $patient?->id] + $d);

        return back()->with('success', __('Saved.'));
    }

    public function updateInteraction(Request $request, string $interaction)
    {
        $this->allow($request, 'crm.interaction.manage');
        $row = $this->find(CrmInteraction::class, $interaction);
        $d = $request->validate(['action' => ['required', 'in:done,assign'], 'outcome' => ['nullable', 'string', 'max:160'], 'assigned_to' => ['nullable', 'integer', 'exists:users,id']]);
        $d['action'] === 'done' ? $this->interactions->complete($row, $d['outcome'] ?? null) : $this->interactions->assign($row, isset($d['assigned_to']) ? User::query()->find($d['assigned_to']) : null);

        return back()->with('success', __('Saved.'));
    }

    public function feedback(Request $request)
    {
        $this->allow($request, 'crm.feedback.view');
        $rows = CrmFeedback::query()->with(['patient:id,first_name,last_name,enterprise_patient_no', 'provider:id,name'])->where('organization_id', $this->organizationId())
            ->when($request->boolean('low'), fn ($q) => $q->where('rating', '<=', 2))->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))->latest('id')->paginate(25)->withQueryString();

        return view('admin.crm.feedback', ['rows' => $rows, 'summary' => $this->feedback->summary($this->organizationId(), now()->subDays(90)->toDateTimeString())]);
    }

    public function reviewFeedback(Request $request, string $feedback)
    {
        $this->allow($request, 'crm.feedback.manage');
        $this->feedback->review($this->find(CrmFeedback::class, $feedback), $request->user());

        return back()->with('success', __('Saved.'));
    }

    private function staff()
    {
        return User::query()->whereHas('organizations', fn ($q) => $q->where('organizations.id', $this->organizationId()))->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }
}
