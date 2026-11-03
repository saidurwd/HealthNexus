<?php

namespace Modules\Portal\Http\Controllers\Web;

use App\Models\Branch;
use App\Models\Crm\CrmCampaign;
use App\Models\Crm\CrmCampaignRecipient;
use App\Models\Crm\CrmSegment;
use App\Services\Crm\CampaignService;
use App\Services\Crm\SegmentService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

/** Groups of patients and messages to them (S11). A second person approves; consent and quiet hours are enforced by the sender, not by this screen. */
class CrmCampaignController extends PortalStaffController
{
    public function __construct(TenantContextResolver $tenant, private readonly SegmentService $segments, private readonly CampaignService $campaigns)
    {
        parent::__construct($tenant);
    }

    public function segments(Request $request)
    {
        $this->allow($request, 'crm.segment.view');

        return view('admin.crm.segments', ['rows' => CrmSegment::query()->where('organization_id', $this->organizationId())->latest('id')->get(), 'branches' => Branch::query()->where('organization_id', $this->organizationId())->orderBy('name')->get(['id', 'name'])]);
    }

    public function storeSegment(Request $request)
    {
        $this->allow($request, 'crm.segment.manage');
        $d = $request->validate(['name' => ['required', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:300'], 'age_from' => ['nullable', 'integer', 'between:0,120'], 'age_to' => ['nullable', 'integer', 'between:0,120'], 'sex' => ['nullable', 'in:M,F,O'],
            'last_visit_before_days' => ['nullable', 'integer', 'min:1'], 'last_visit_within_days' => ['nullable', 'integer', 'min:1'], 'has_portal' => ['nullable', 'in:0,1'], 'branch_id' => ['nullable', 'integer'], 'language' => ['nullable', 'in:bn,en'],
            'visits_at_least' => ['nullable', 'integer', 'min:1'], 'problem_code_prefix' => ['nullable', 'string', 'max:10'], 'clinical_basis' => ['nullable', 'string', 'max:300']]);
        $criteria = collect($d)->except(['name', 'description', 'clinical_basis'])->filter(fn ($v) => $v !== null && $v !== '')->map(fn ($v, $k) => $k === 'has_portal' ? (bool) $v : $v)->all();
        $this->segments->create($this->organizationId(), $request->user(), $d['name'], $criteria, $d['description'] ?? null, $d['clinical_basis'] ?? null);

        return back()->with('success', __('Saved. It must be approved before it can be used.'));
    }

    public function segmentAction(Request $request, string $segment)
    {
        $row = $this->find(CrmSegment::class, $segment);
        $d = $request->validate(['action' => ['required', 'in:activate,retire,count']]);
        if ($d['action'] === 'activate') {
            $this->allow($request, 'crm.segment.approve');
            $this->segments->activate($row, $request->user());
        } elseif ($d['action'] === 'retire') {
            $this->allow($request, 'crm.segment.manage');
            $this->segments->retire($row);
        } else {
            $this->allow($request, 'crm.segment.view');
            $row->update(['last_count' => $this->segments->count($row), 'last_counted_at' => now()]);
        }

        return back()->with('success', __('Saved.'));
    }

    public function campaigns(Request $request)
    {
        $this->allow($request, 'crm.campaign.view');

        return view('admin.crm.campaigns', ['rows' => CrmCampaign::query()->with('segment:id,name')->where('organization_id', $this->organizationId())->latest('id')->paginate(20), 'segments' => CrmSegment::query()->where(['organization_id' => $this->organizationId(), 'status' => 'active'])->orderBy('name')->get(['id', 'name', 'last_count'])]);
    }

    public function storeCampaign(Request $request)
    {
        $this->allow($request, 'crm.campaign.manage');
        $d = $request->validate(['name' => ['required', 'string', 'max:120'], 'purpose' => ['required', 'in:service,marketing'], 'channel' => ['required', 'in:sms,email,push,inapp'], 'segment_id' => ['required', 'integer'], 'subject' => ['nullable', 'string', 'max:160'], 'body' => ['required', 'string', 'max:1000'], 'scheduled_at' => ['nullable', 'date']]);
        $this->campaigns->create($this->organizationId(), $request->user(), $d);

        return back()->with('success', __('Saved.'));
    }

    public function show(Request $request, string $campaign)
    {
        $this->allow($request, 'crm.campaign.view');
        $row = $this->find(CrmCampaign::class, $campaign)->load('segment');

        return view('admin.crm.campaign', ['c' => $row, 'recipients' => CrmCampaignRecipient::query()->with('patient:id,first_name,last_name,enterprise_patient_no')->where('campaign_id', $row->id)->orderBy('status')->paginate(40), 'reasons' => CrmCampaignRecipient::query()->where('campaign_id', $row->id)->where('status', 'suppressed')->selectRaw('reason, count(*) c')->groupBy('reason')->pluck('c', 'reason')->all()]);
    }

    public function action(Request $request, string $campaign)
    {
        $row = $this->find(CrmCampaign::class, $campaign);
        $d = $request->validate(['action' => ['required', 'in:submit,approve,run,cancel']]);
        if ($d['action'] === 'submit') {
            $this->allow($request, 'crm.campaign.manage');
            $this->campaigns->submit($row);
        } elseif ($d['action'] === 'approve') {
            $this->allow($request, 'crm.campaign.approve');
            $this->campaigns->approve($row, $request->user());
        } elseif ($d['action'] === 'run') {
            $this->allow($request, 'crm.campaign.approve');
            $this->campaigns->run($row);
        } else {
            $this->allow($request, 'crm.campaign.manage');
            $this->campaigns->cancel($row);
        }

        return back()->with('success', __('Saved.'));
    }
}
