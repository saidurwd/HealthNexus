<?php

namespace Modules\Quality\Http\Controllers\Web;

use App\Models\Crm\CrmComplaint;
use App\Models\Hr\HrEmployee;
use App\Models\Quality\QaAssessment;
use App\Models\Quality\QaCommittee;
use App\Models\Quality\QaComplaintLink;
use App\Models\Quality\QaComplianceItem;
use App\Models\Quality\QaIncident;
use App\Models\Quality\QaMeeting;
use App\Models\Quality\QaMeetingItem;
use App\Models\Quality\QaMortalityReview;
use App\Models\Quality\QaPrivilege;
use App\Models\Quality\QaPrivilegeCatalog;
use App\Models\Quality\QaStandard;
use App\Services\Quality\AccreditationService;
use App\Services\Quality\ComplaintLinkService;
use App\Services\Quality\ComplianceService;
use App\Services\Quality\MeetingService;
use App\Services\Quality\PrivilegeService;
use App\Services\Quality\QualityPolicy;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

/** Privileges, the compliance calendar, accreditation checklists, committees and meetings, and complaints linked to incidents. */
class GovernanceController extends QualityController
{
    public function __construct(TenantContextResolver $tenant, private readonly PrivilegeService $privileges, private readonly ComplianceService $compliance, private readonly AccreditationService $accreditation,
        private readonly MeetingService $meetings, private readonly ComplaintLinkService $links, private readonly QualityPolicy $policy)
    {
        parent::__construct($tenant);
    }

    // ---- privileges -------------------------------------------------------------------------------------------------------------------------------------------------------

    public function privileges(Request $request)
    {
        $this->allow($request, 'quality.privilege.view');
        $org = $this->organizationId();
        $status = $request->query('status', 'granted');

        return view('admin.quality.privileges', [
            'rows' => QaPrivilege::query()->with('item')->where('organization_id', $org)->when($status !== 'all', fn ($q) => $q->where('status', $status))->orderBy('expires_on')->paginate(30)->withQueryString(),
            'names' => HrEmployee::query()->where('organization_id', $org)->get(['id', 'first_name', 'last_name'])->mapWithKeys(fn ($e) => [$e->id => trim($e->first_name.' '.$e->last_name)]),
            'catalog' => QaPrivilegeCatalog::query()->where('organization_id', $org)->where('active', true)->orderBy('name')->get(), 'status' => $status,
            'employees' => HrEmployee::query()->where('organization_id', $org)->whereIn('status', ['active', 'on_leave'])->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_no']),
            'can' => ['request' => $request->user()->can('quality.privilege.request'), 'decide' => $request->user()->can('quality.privilege.decide'), 'manage' => $request->user()->can('quality.privilege.manage')],
        ]);
    }

    public function saveCatalog(Request $request)
    {
        $this->allow($request, 'quality.privilege.manage');
        $d = $request->validate(['code' => ['required', 'string', 'max:30'], 'name' => ['required', 'string', 'max:150'], 'requires_credential' => ['nullable', 'string', 'max:20'], 'validity_months' => ['nullable', 'integer', 'min:1', 'max:120']]);
        $this->privileges->addToCatalog($this->organizationId(), $d['code'], $d['name'], $d['requires_credential'] ?? null, isset($d['validity_months']) ? (int) $d['validity_months'] : null);

        return back()->with('success', __('Saved.'));
    }

    public function requestPrivilege(Request $request)
    {
        $this->allow($request, 'quality.privilege.request');
        $d = $request->validate(['employee_id' => ['required', 'integer'], 'catalog_id' => ['required', 'integer'], 'note' => ['nullable', 'string', 'max:500']]);
        $org = $this->organizationId();
        $this->privileges->request($org, HrEmployee::query()->where('organization_id', $org)->findOrFail($d['employee_id']), QaPrivilegeCatalog::query()->where('organization_id', $org)->findOrFail($d['catalog_id']), $request->user(), $d['note'] ?? null);

        return back()->with('success', __('The request was sent for a decision.'));
    }

    public function decidePrivilege(Request $request, string $privilege)
    {
        $this->allow($request, 'quality.privilege.decide');
        $d = $request->validate(['decision' => ['required', 'in:grant,decline'], 'note' => ['nullable', 'string', 'max:500']]);
        $this->privileges->decide($this->find(QaPrivilege::class, $privilege), $request->user(), $d['decision'] === 'grant', $d['note'] ?? null);

        return back()->with('success', __('Saved.'));
    }

    public function withdrawPrivilege(Request $request, string $privilege)
    {
        $this->allow($request, 'quality.privilege.manage');
        $d = $request->validate(['reason' => ['required', 'string', 'max:500'], 'revoke' => ['nullable', 'boolean']]);
        $this->privileges->withdraw($this->find(QaPrivilege::class, $privilege), $request->user(), (bool) ($d['revoke'] ?? false), $d['reason']);

        return back()->with('success', __('Saved.'));
    }

    // ---- compliance calendar ----------------------------------------------------------------------------------------------------------------------------------------------

    public function compliance(Request $request)
    {
        $this->allow($request, 'quality.compliance.view');

        return view('admin.quality.compliance', ['items' => QaComplianceItem::query()->with('owner')->where('organization_id', $this->organizationId())->where('status', 'active')->orderBy('due_on')->get(), 'service' => $this->compliance, 'kinds' => $this->policy->list('compliance.kinds'),
            'staff' => $this->staff(), 'canManage' => $request->user()->can('quality.compliance.manage')]);
    }

    public function storeCompliance(Request $request)
    {
        $this->allow($request, 'quality.compliance.manage');
        $d = $request->validate(['kind' => ['required', 'string', 'max:12'], 'title' => ['required', 'string', 'max:200'], 'authority' => ['nullable', 'string', 'max:150'], 'reference' => ['nullable', 'string', 'max:80'], 'due_on' => ['required', 'date'], 'interval_months' => ['nullable', 'integer', 'min:1', 'max:120'], 'owner_id' => ['nullable', 'integer']]);
        $this->compliance->add($this->organizationId(), $d + ['branch_id' => $this->branchId()], $request->user());

        return back()->with('success', __('Added to the calendar.'));
    }

    public function completeCompliance(Request $request, string $item)
    {
        $this->allow($request, 'quality.compliance.manage');
        $d = $request->validate(['done_on' => ['nullable', 'date'], 'next_due' => ['nullable', 'date']]);
        $this->compliance->complete($this->find(QaComplianceItem::class, $item), $request->user(), $d['done_on'] ?? null, $d['next_due'] ?? null);

        return back()->with('success', __('Saved.'));
    }

    // ---- accreditation ----------------------------------------------------------------------------------------------------------------------------------------------------------

    public function accreditation(Request $request)
    {
        $this->allow($request, 'quality.accreditation.view');
        $org = $this->organizationId();

        return view('admin.quality.accreditation.index', ['assessments' => QaAssessment::query()->with('standard')->where('organization_id', $org)->orderByDesc('started_on')->get(), 'standards' => QaStandard::query()->withCount('items')->where('organization_id', $org)->where('active', true)->get(),
            'canManage' => $request->user()->can('quality.accreditation.manage')]);
    }

    public function startAssessment(Request $request)
    {
        $this->allow($request, 'quality.accreditation.manage');
        $d = $request->validate(['standard_id' => ['required', 'integer'], 'name' => ['nullable', 'string', 'max:200']]);
        $standard = QaStandard::query()->where('organization_id', $this->organizationId())->findOrFail($d['standard_id']);
        $a = $this->accreditation->start($this->organizationId(), $standard, (string) ($d['name'] ?? ''), $request->user(), $this->branchId());

        return redirect()->route('admin.quality.accreditation.show', $a->public_id);
    }

    public function assessment(Request $request, string $assessment)
    {
        $this->allow($request, 'quality.accreditation.view');
        $a = $this->find(QaAssessment::class, $assessment);
        $a->load('standard', 'results.item', 'results.action.owner');

        return view('admin.quality.accreditation.show', ['a' => $a, 'score' => $this->accreditation->score($a), 'canManage' => $request->user()->can('quality.accreditation.manage'), 'staff' => $this->staff(), 'results' => array_values(array_diff(AccreditationService::RESULTS, ['not_assessed']))]);
    }

    public function recordResult(Request $request, string $assessment, int $result)
    {
        $this->allow($request, 'quality.accreditation.manage');
        $d = $request->validate(['result' => ['required', 'string', 'max:14'], 'evidence' => ['nullable', 'string', 'max:1000']]);
        $a = $this->find(QaAssessment::class, $assessment);
        $this->accreditation->record($a, $a->results()->findOrFail($result), $request->user(), $d['result'], $d['evidence'] ?? null);

        return back()->with('success', __('Saved.'));
    }

    public function gapAction(Request $request, string $assessment, int $result)
    {
        $this->allow($request, 'quality.accreditation.manage', 'quality.action.manage');
        $d = $request->validate(['owner_id' => ['required', 'integer'], 'due_on' => ['nullable', 'date'], 'description' => ['nullable', 'string', 'max:1000']]);
        $a = $this->find(QaAssessment::class, $assessment);
        $this->accreditation->gapAction($a, $a->results()->with('item')->findOrFail($result), $d, $request->user());

        return back()->with('success', __('An action was raised for the gap.'));
    }

    public function completeAssessment(Request $request, string $assessment)
    {
        $this->allow($request, 'quality.accreditation.manage');
        $this->accreditation->complete($this->find(QaAssessment::class, $assessment), $request->user());

        return back()->with('success', __('The assessment is complete.'));
    }

    // ---- committees -------------------------------------------------------------------------------------------------------------------------------------------------------------

    public function committees(Request $request)
    {
        $this->allow($request, 'quality.committee.view');
        $org = $this->organizationId();

        return view('admin.quality.committees', ['committees' => QaCommittee::query()->with('chair')->withCount('members')->where('organization_id', $org)->where('active', true)->get(),
            'meetings' => QaMeeting::query()->with('committee')->where('organization_id', $org)->orderByDesc('scheduled_at')->limit(40)->get(), 'canManage' => $request->user()->can('quality.committee.manage')]);
    }

    public function scheduleMeeting(Request $request)
    {
        $this->allow($request, 'quality.committee.manage');
        $d = $request->validate(['committee_id' => ['required', 'integer'], 'title' => ['required', 'string', 'max:200'], 'scheduled_at' => ['required', 'date'], 'agenda' => ['nullable', 'string', 'max:4000'], 'location' => ['nullable', 'string', 'max:120']]);
        $m = $this->meetings->schedule(QaCommittee::query()->where('organization_id', $this->organizationId())->findOrFail($d['committee_id']), $d['title'], $d['scheduled_at'], $d['agenda'] ?? null, $d['location'] ?? null);

        return redirect()->route('admin.quality.meetings.show', $m->public_id)->with('success', __('The meeting was scheduled. Add the cases to discuss.'));
    }

    public function meeting(Request $request, string $meeting)
    {
        $this->allow($request, 'quality.committee.view');
        $m = $this->find(QaMeeting::class, $meeting);
        $m->load('committee', 'items.action.owner');

        return view('admin.quality.meeting', ['m' => $m, 'canManage' => $request->user()->can('quality.committee.manage'), 'staff' => $this->staff(), 'attendees' => $this->staff()->whereIn('id', (array) $m->attendees)->pluck('name'),
            'incidents' => QaIncident::query()->where('organization_id', $this->organizationId())->where('status', '!=', 'closed')->orderByDesc('occurred_at')->limit(40)->get(['id', 'number', 'category']),
            'deaths' => QaMortalityReview::query()->where('organization_id', $this->organizationId())->whereIn('status', ['reviewed', 'pending'])->orderByDesc('died_at')->limit(40)->get(['id', 'number', 'classification'])]);
    }

    public function addItem(Request $request, string $meeting)
    {
        $this->allow($request, 'quality.committee.manage');
        $d = $request->validate(['kind' => ['required', 'in:incident,mortality,topic'], 'ref_id' => ['nullable', 'integer'], 'title' => ['nullable', 'string', 'max:250']]);
        $this->meetings->addItem($this->find(QaMeeting::class, $meeting), $d['kind'], isset($d['ref_id']) ? (int) $d['ref_id'] : null, $d['title'] ?? null);

        return back()->with('success', __('Added to the agenda.'));
    }

    public function hold(Request $request, string $meeting)
    {
        $this->allow($request, 'quality.committee.manage');
        $d = $request->validate(['attendees' => ['nullable', 'array'], 'attendees.*' => ['integer']]);
        $this->meetings->hold($this->find(QaMeeting::class, $meeting), $d['attendees'] ?? [], $request->user());

        return back()->with('success', __('The meeting was held. Record what was said about each case.'));
    }

    public function recordItem(Request $request, string $meeting, int $item)
    {
        $this->allow($request, 'quality.committee.manage');
        $d = $request->validate(['discussion' => ['nullable', 'string', 'max:6000'], 'outcome' => ['required', 'string', 'max:1000'], 'action' => ['nullable', 'string', 'max:1000'], 'owner_id' => ['nullable', 'integer'], 'due_on' => ['nullable', 'date']]);
        $m = $this->find(QaMeeting::class, $meeting);
        $this->meetings->recordItem($m, QaMeetingItem::query()->where('meeting_id', $m->id)->findOrFail($item), $request->user(), $d['discussion'] ?? null, $d['outcome'],
            ! empty($d['action']) && ! empty($d['owner_id']) ? ['description' => $d['action'], 'owner_id' => $d['owner_id'], 'due_on' => $d['due_on'] ?? null] : null);

        return back()->with('success', __('Saved.'));
    }

    public function minute(Request $request, string $meeting)
    {
        $this->allow($request, 'quality.committee.manage');
        $d = $request->validate(['minutes' => ['required', 'string', 'max:12000']]);
        $this->meetings->minute($this->find(QaMeeting::class, $meeting), $request->user(), $d['minutes']);

        return back()->with('success', __('The minutes are saved.'));
    }

    // ---- complaints -------------------------------------------------------------------------------------------------------------------------------------------------------------

    public function complaints(Request $request)
    {
        $this->allow($request, 'quality.complaint.link');
        $org = $this->organizationId();
        $linked = QaComplaintLink::query()->where('organization_id', $org)->whereNotNull('incident_id')->pluck('incident_id', 'complaint_id');

        return view('admin.quality.complaints', ['complaints' => CrmComplaint::query()->where('organization_id', $org)->orderByDesc('created_at')->paginate(25), 'linked' => $linked, 'incidents' => QaIncident::query()->whereIn('id', $linked->values())->pluck('public_id', 'id')]);
    }

    public function raiseComplaint(Request $request, string $complaint)
    {
        $this->allow($request, 'quality.complaint.link');
        $d = $request->validate(['harm' => ['nullable', 'string', 'max:12']]);
        $incident = $this->links->raise($this->find(CrmComplaint::class, $complaint), $request->user(), $d['harm'] ?? null);

        return redirect()->route('admin.quality.incidents.show', $incident->public_id)->with('success', __('The complaint was raised as incident :n.', ['n' => $incident->number]));
    }
}
