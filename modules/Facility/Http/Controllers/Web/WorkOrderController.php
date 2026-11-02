<?php

namespace Modules\Facility\Http\Controllers\Web;

use App\Models\Facility\FacAsset;
use App\Models\Facility\FacWorkOrder;
use App\Services\Facility\MaintenanceService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

/** Work orders: the list, one order, and each step. */
class WorkOrderController extends FacilityController
{
    public function __construct(TenantContextResolver $tenant, private readonly MaintenanceService $maintenance)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'facility.workorder.view', 'facility.workorder.manage');
        $status = $request->query('status', 'open');
        $orders = FacWorkOrder::query()->with('asset', 'assignee')->where('organization_id', $this->organizationId())
            ->when($status === 'open', fn ($q) => $q->whereIn('status', ['open', 'in_progress', 'waiting_parts']))->when($status === 'to_check', fn ($q) => $q->where('status', 'done')->whereHas('asset', fn ($w) => $w->where('risk_class', 'high'))->whereIn('kind', ['breakdown', 'calibration']))
            ->when(! in_array($status, ['open', 'to_check', 'all'], true), fn ($q) => $q->where('status', $status))->orderByRaw("case priority when 'urgent' then 0 when 'normal' then 1 else 2 end")->orderBy('reported_at')->paginate(25)->withQueryString();

        return view('admin.facility.workorders.index', ['orders' => $orders, 'status' => $status, 'assets' => FacAsset::query()->where('organization_id', $this->organizationId())->where('status', '!=', 'retired')->orderBy('tag')->get(['id', 'tag', 'name']), 'canManage' => $request->user()->can('facility.workorder.manage')]);
    }

    public function store(Request $request)
    {
        $this->allow($request, 'facility.workorder.manage');
        $d = $request->validate(['asset_id' => ['nullable', 'integer'], 'kind' => ['required', 'string', 'max:12'], 'title' => ['required', 'string', 'max:200'], 'description' => ['nullable', 'string', 'max:4000'], 'priority' => ['nullable', 'string', 'max:8'], 'due_on' => ['nullable', 'date']]);
        $wo = $this->maintenance->create($this->organizationId(), $d + ['branch_id' => $this->branchId()], $request->user());

        return redirect()->route('admin.facility.workorders.show', $wo->public_id)->with('success', __('The work order was made.'));
    }

    public function show(Request $request, string $order)
    {
        $this->allow($request, 'facility.workorder.view', 'facility.workorder.manage');
        $wo = $this->find(FacWorkOrder::class, $order);
        $wo->load('asset', 'parts', 'assignee', 'reporter');

        return view('admin.facility.workorders.show', ['w' => $wo, 'staff' => $this->staff(), 'me' => $request->user()->id, 'can' => ['manage' => $request->user()->can('facility.workorder.manage'), 'verify' => $request->user()->can('facility.workorder.verify')]]);
    }

    public function assign(Request $request, string $order)
    {
        $this->allow($request, 'facility.workorder.manage');
        $d = $request->validate(['engineer_id' => ['required', 'integer']]);
        $this->maintenance->assign($this->find(FacWorkOrder::class, $order), $this->staff()->firstWhere('id', (int) $d['engineer_id']) ?? abort(404));

        return back()->with('success', __('Saved.'));
    }

    public function start(Request $request, string $order)
    {
        $this->allow($request, 'facility.workorder.manage');
        $this->maintenance->start($this->find(FacWorkOrder::class, $order), $request->user());

        return back()->with('success', __('Started.'));
    }

    public function wait(Request $request, string $order)
    {
        $this->allow($request, 'facility.workorder.manage');
        $d = $request->validate(['note' => ['required', 'string', 'max:1000']]);
        $this->maintenance->waitForParts($this->find(FacWorkOrder::class, $order), $d['note']);

        return back()->with('success', __('Saved.'));
    }

    public function part(Request $request, string $order)
    {
        $this->allow($request, 'facility.workorder.manage');
        $d = $request->validate(['description' => ['required', 'string', 'max:200'], 'quantity' => ['required', 'numeric', 'min:0.01'], 'unit_cost' => ['nullable', 'numeric', 'min:0']]);
        $this->maintenance->addPart($this->find(FacWorkOrder::class, $order), $d['description'], (float) $d['quantity'], (float) ($d['unit_cost'] ?? 0));

        return back()->with('success', __('The part was added.'));
    }

    public function complete(Request $request, string $order)
    {
        $this->allow($request, 'facility.workorder.manage');
        $d = $request->validate(['note' => ['required', 'string', 'max:1000']]);
        $this->maintenance->complete($this->find(FacWorkOrder::class, $order), $request->user(), $d['note']);

        return back()->with('success', __('Saved.'));
    }

    public function verify(Request $request, string $order)
    {
        $this->allow($request, 'facility.workorder.verify');
        $this->maintenance->verify($this->find(FacWorkOrder::class, $order), $request->user());

        return back()->with('success', __('The work was checked.'));
    }

    public function cancel(Request $request, string $order)
    {
        $this->allow($request, 'facility.workorder.manage');
        $d = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $this->maintenance->cancel($this->find(FacWorkOrder::class, $order), $request->user(), $d['reason']);

        return back()->with('success', __('The work order was cancelled.'));
    }
}
