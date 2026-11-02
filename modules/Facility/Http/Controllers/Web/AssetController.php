<?php

namespace Modules\Facility\Http\Controllers\Web;

use App\Models\Department;
use App\Models\Facility\FacAsset;
use App\Services\Facility\AssetService;
use App\Services\Facility\FacilityPolicy;
use App\Services\Facility\MaintenanceService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

/** The equipment register: list, one asset with its plans, orders and calibrations, label, scan, breakdown. */
class AssetController extends FacilityController
{
    public function __construct(TenantContextResolver $tenant, private readonly AssetService $assets, private readonly MaintenanceService $maintenance, private readonly FacilityPolicy $policy)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'facility.asset.view', 'facility.workorder.report');
        $f = $request->validate(['q' => ['nullable', 'string', 'max:60'], 'category' => ['nullable', 'string', 'max:12'], 'status' => ['nullable', 'string', 'max:16']]);
        $assets = FacAsset::query()->where('organization_id', $this->organizationId())->when($f['category'] ?? null, fn ($q, $v) => $q->where('category', $v))->when($f['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($f['q'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w->where('tag', 'like', "%$v%")->orWhere('name', 'like', "%$v%")->orWhere('serial_no', 'like', "%$v%")))->orderBy('tag')->paginate(30)->withQueryString();

        return view('admin.facility.assets.index', ['assets' => $assets, 'filters' => $f, 'policy' => $this->policy, 'service' => $this->assets, 'departments' => Department::query()->where('organization_id', $this->organizationId())->orderBy('name')->get(['id', 'name']),
            'canManage' => $request->user()->can('facility.asset.manage')]);
    }

    public function store(Request $request)
    {
        $this->allow($request, 'facility.asset.manage');
        $d = $request->validate(['name' => ['required', 'string', 'max:150'], 'category' => ['required', 'string', 'max:12'], 'tag' => ['nullable', 'string', 'max:30'], 'manufacturer' => ['nullable', 'string', 'max:100'], 'model' => ['nullable', 'string', 'max:100'],
            'serial_no' => ['nullable', 'string', 'max:80'], 'supplier_name' => ['nullable', 'string', 'max:150'], 'department_id' => ['nullable', 'integer'], 'location' => ['nullable', 'string', 'max:120'], 'purchased_on' => ['nullable', 'date'], 'cost' => ['nullable', 'numeric', 'min:0'],
            'warranty_until' => ['nullable', 'date'], 'risk_class' => ['nullable', 'string', 'max:6']]);
        $asset = $this->assets->register($this->organizationId(), $d + ['branch_id' => $this->branchId()], $request->user());

        return redirect()->route('admin.facility.assets.show', $asset->public_id)->with('success', __('The equipment was added. Print its label.'));
    }

    public function show(Request $request, string $asset)
    {
        $this->allow($request, 'facility.asset.view', 'facility.workorder.report');
        $a = $this->find(FacAsset::class, $asset);
        $a->load('plans', 'department');

        return view('admin.facility.assets.show', ['a' => $a, 'reason' => $this->assets->reason($a), 'orders' => $a->workOrders()->orderByDesc('id')->limit(15)->get(), 'calibrations' => $a->calibrations()->orderByDesc('performed_on')->limit(10)->get(),
            'can' => ['manage' => $request->user()->can('facility.asset.manage'), 'calibrate' => $request->user()->can('facility.calibration.record'), 'label' => $request->user()->can('facility.asset.label') || $request->user()->can('facility.asset.manage')]]);
    }

    public function label(Request $request, string $asset)
    {
        $this->allow($request, 'facility.asset.label', 'facility.asset.manage');
        $a = $this->find(FacAsset::class, $asset);

        return view('admin.facility.assets.label', ['a' => $a, 'qr' => $this->assets->qrSvg($a, 140)]);
    }

    /** Scanning a label opens the asset (a staff sign-in is needed first). */
    public function scan(Request $request, string $token)
    {
        $this->allow($request, 'facility.asset.view', 'facility.workorder.report');
        $a = $this->assets->findByToken($token, $this->organizationId()) ?? abort(404);

        return redirect()->route('admin.facility.assets.show', $a->public_id);
    }

    public function move(Request $request, string $asset)
    {
        $this->allow($request, 'facility.asset.manage');
        $d = $request->validate(['location' => ['nullable', 'string', 'max:120'], 'department_id' => ['nullable', 'integer']]);
        $this->assets->move($this->find(FacAsset::class, $asset), $d['location'] ?? null, $d['department_id'] ?? null);

        return back()->with('success', __('Saved.'));
    }

    public function retire(Request $request, string $asset)
    {
        $this->allow($request, 'facility.asset.manage');
        $d = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $this->assets->retire($this->find(FacAsset::class, $asset), $request->user(), $d['reason']);

        return back()->with('success', __('The equipment is retired.'));
    }

    public function plan(Request $request, string $asset)
    {
        $this->allow($request, 'facility.workorder.manage');
        $d = $request->validate(['kind' => ['required', 'string', 'max:14'], 'title' => ['required', 'string', 'max:150'], 'interval_days' => ['required', 'integer', 'min:1', 'max:3650'], 'first_due' => ['nullable', 'date']]);
        $this->maintenance->plan($this->find(FacAsset::class, $asset), $d['kind'], $d['title'], (int) $d['interval_days'], $d['first_due'] ?? null);

        return back()->with('success', __('The plan was added.'));
    }

    public function calibrate(Request $request, string $asset)
    {
        $this->allow($request, 'facility.calibration.record');
        $d = $request->validate(['result' => ['required', 'string', 'max:10'], 'performed_on' => ['required', 'date'], 'next_due_on' => ['nullable', 'date'], 'certificate_no' => ['nullable', 'string', 'max:60'], 'performed_by' => ['nullable', 'string', 'max:120'], 'note' => ['nullable', 'string', 'max:500']]);
        $this->maintenance->calibrate($this->find(FacAsset::class, $asset), $request->user(), $d['result'], $d['performed_on'], $d['next_due_on'] ?? null, $d['certificate_no'] ?? null, $d['performed_by'] ?? null, null, $d['note'] ?? null);

        return back()->with('success', __('The calibration was recorded.'));
    }

    /** Anyone may report a device as broken. */
    public function breakdown(Request $request, string $asset)
    {
        $this->allow($request, 'facility.workorder.report', 'facility.workorder.manage');
        $d = $request->validate(['description' => ['required', 'string', 'max:2000'], 'priority' => ['nullable', 'string', 'max:8'], 'stop_use' => ['nullable', 'boolean']]);
        $wo = $this->maintenance->reportBreakdown($this->find(FacAsset::class, $asset), $request->user(), $d['description'], $d['priority'] ?? 'urgent', (bool) ($d['stop_use'] ?? true));

        return redirect()->route('admin.facility.assets.show', $asset)->with('success', __('Reported as :n. The engineers have been told.', ['n' => $wo->number]));
    }
}
