<?php

namespace Modules\Portal\Http\Controllers\Web;

use App\Models\Laboratory\LabTestCategory;
use App\Models\Portal\PortalRefillRequest;
use App\Models\Portal\PortalReleasePolicy;
use App\Models\Portal\PortalResultRelease;
use App\Services\Portal\RefillService;
use App\Services\Portal\ResultReleaseService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

/** What the clinician decides about the patient's screen: results to release or hold, release rules, refill requests (S04, S05). */
class PortalReleaseDeskController extends PortalStaffController
{
    public function __construct(TenantContextResolver $tenant, private readonly ResultReleaseService $release, private readonly RefillService $refills)
    {
        parent::__construct($tenant);
    }

    public function queue(Request $request)
    {
        $this->allow($request, 'portal.release.view');
        $this->release->sync($this->organizationId(), 300);
        $status = $request->query('status', 'open');
        $rows = PortalResultRelease::query()->with('patient:id,public_id,first_name,last_name,enterprise_patient_no')->where('organization_id', $this->organizationId())
            ->when($status === 'open', fn ($q) => $q->whereIn('status', ['pending', 'held_critical']))->when($status !== 'open' && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderByDesc('critical')->orderBy('reported_at')->paginate(25)->withQueryString();

        return view('admin.portal.releases', ['rows' => $rows, 'status' => $status, 'ack' => fn ($r) => $this->release->criticalAcknowledged($r)]);
    }

    public function decide(Request $request, string $release)
    {
        $this->allow($request, 'portal.release.manage');
        $row = $this->find(PortalResultRelease::class, $release);
        $d = $request->validate(['decision' => ['required', 'in:release,withhold'], 'reason' => ['nullable', 'string', 'max:200']]);
        if ($d['decision'] === 'withhold') {
            $request->validate(['reason' => ['required', 'string', 'max:200']]);
            $this->release->withhold($row, $request->user(), $d['reason']);
        } else {
            $this->release->release($row, $request->user(), $d['reason'] ?? null);
        }

        return back()->with('success', __('Saved.'));
    }

    public function policies(Request $request)
    {
        $this->allow($request, 'portal.release.view');

        return view('admin.portal.policies', [
            'rows' => PortalReleasePolicy::query()->where('organization_id', $this->organizationId())->orderBy('scope_type')->orderBy('scope_key')->get(),
            'categories' => LabTestCategory::query()->where('organization_id', $this->organizationId())->orderBy('name')->get(['code', 'name']),
        ]);
    }

    public function savePolicy(Request $request)
    {
        $this->allow($request, 'portal.release.policy');
        $d = $request->validate(['scope_type' => ['required', 'in:default,lab_category,radiology'], 'scope_key' => ['nullable', 'string', 'max:60'], 'mode' => ['required', 'in:immediate,after_review,delayed,never'],
            'delay_hours' => ['nullable', 'integer', 'between:0,720'], 'sensitive' => ['nullable', 'boolean'], 'note' => ['nullable', 'string', 'max:200']]);
        $key = $d['scope_type'] === 'lab_category' ? (string) ($d['scope_key'] ?: 'all') : 'all';
        PortalReleasePolicy::query()->updateOrCreate(['organization_id' => $this->organizationId(), 'scope_type' => $d['scope_type'], 'scope_key' => $key],
            ['mode' => $d['mode'], 'delay_hours' => (int) ($d['delay_hours'] ?? 0), 'sensitive' => (bool) ($d['sensitive'] ?? false), 'note' => $d['note'] ?? null, 'updated_by' => $request->user()->id]);

        return back()->with('success', __('Saved. It applies to reports that are made from now on.'));
    }

    public function refills(Request $request)
    {
        $this->allow($request, 'portal.refill.review');
        $status = $request->query('status', 'requested');
        $rows = PortalRefillRequest::query()->with(['patient:id,public_id,first_name,last_name,enterprise_patient_no', 'prescription.items', 'prescription.doctor:id,name'])->where('organization_id', $this->organizationId())
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))->oldest('id')->paginate(25)->withQueryString();

        return view('admin.portal.refills', ['rows' => $rows, 'status' => $status]);
    }

    public function decideRefill(Request $request, string $refill)
    {
        $this->allow($request, 'portal.refill.review');
        $row = $this->find(PortalRefillRequest::class, $refill);
        $d = $request->validate(['decision' => ['required', 'in:approved,declined,needs_visit'], 'note' => ['nullable', 'string', 'max:300']]);
        $this->refills->decide($row, $request->user(), $d['decision'], $d['note'] ?? null);

        return back()->with('success', __('Saved.'));
    }
}
