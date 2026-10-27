<?php

namespace Modules\Inventory\Http\Controllers\Web;

use App\Models\Inventory\InventoryCategory;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryStock;
use App\Models\Inventory\InventoryStore;
use App\Services\Inventory\InventoryMasterService;
use App\Services\Inventory\InventoryReportService;
use App\Services\TenantContextResolver;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Dashboard, item master, stores, stock on hand and the management reports. */
class InventoryDeskController extends InventoryController
{
    public function __construct(TenantContextResolver $tenant, private readonly InventoryMasterService $masters, private readonly InventoryReportService $reports)
    {
        parent::__construct($tenant);
    }

    public function dashboard(Request $request)
    {
        $this->allow($request, 'inventory.dashboard.view');

        return view('admin.inventory.dashboard', ['d' => $this->reports->dashboard($this->organizationId())]);
    }

    public function items(Request $request)
    {
        $this->allow($request, 'inventory.item.view');
        $org = $this->organizationId();
        $items = InventoryItem::query()->with('category:id,name')->where('organization_id', $org)
            ->when($request->query('q'), fn ($q, $v) => $q->where(fn ($w) => $w->where('code', 'like', "%$v%")->orWhere('name', 'like', "%$v%")))
            ->when($request->query('category'), fn ($q, $v) => $q->where('category_id', $v))->orderBy('code')->paginate(30)->withQueryString();

        return view('admin.inventory.items', ['items' => $items, 'categories' => InventoryCategory::where('organization_id', $org)->orderBy('name')->get(), 'kinds' => config('inventory.category_kinds'), 'uoms' => config('inventory.uoms')]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $this->allow($request, 'inventory.item.manage');
        $v = $request->validate(['code' => ['required', 'string', 'max:30'], 'name' => ['required', 'string', 'max:120'], 'kind' => ['required', Rule::in(config('inventory.category_kinds'))]]);
        $this->masters->createCategory($this->organizationId(), $v);

        return back()->with('success', __('Category added.'));
    }

    public function storeItem(Request $request): RedirectResponse
    {
        $this->allow($request, 'inventory.item.manage');
        $v = $request->validate([
            'category_id' => ['required', 'integer'], 'code' => ['required', 'string', 'max:40'], 'name' => ['required', 'string', 'max:200'], 'base_uom' => ['required', 'string', 'max:20'],
            'purchase_uom' => ['nullable', 'string', 'max:20'], 'purchase_to_base' => ['nullable', 'numeric', 'gt:0'], 'tracks_batch' => ['nullable', 'boolean'], 'tracks_expiry' => ['nullable', 'boolean'],
            'issue_rule' => ['nullable', Rule::in(config('inventory.issue_rules'))], 'reorder_min' => ['nullable', 'numeric', 'min:0'], 'reorder_max' => ['nullable', 'numeric', 'min:0'],
            'lead_time_days' => ['nullable', 'integer', 'min:0', 'max:365'], 'standard_cost' => ['nullable', 'numeric', 'min:0'], 'charge_service_code' => ['nullable', 'string', 'max:40'],
        ]);
        $category = InventoryCategory::where('organization_id', $this->organizationId())->findOrFail($v['category_id']);
        unset($v['category_id']);
        $v['tracks_batch'] = $request->boolean('tracks_batch') || $request->boolean('tracks_expiry');
        $v['tracks_expiry'] = $request->boolean('tracks_expiry');
        $v = array_filter($v, fn ($x) => $x !== null);
        $item = $this->masters->createItem($category, $v);

        return redirect()->route('admin.inventory.items.show', $item)->with('success', __('Item added.'));
    }

    public function showItem(Request $request, InventoryItem $item)
    {
        $this->scoped($request, $item, 'inventory.item.view');

        return view('admin.inventory.item', [
            'item' => $item->load('category', 'uoms'),
            'positions' => InventoryStock::query()->with(['store:id,name,kind', 'batch'])->where('item_id', $item->id)->where('quantity_on_hand', '!=', 0)->orderBy('store_id')->get(),
        ]);
    }

    public function stores(Request $request)
    {
        $this->allow($request, 'inventory.store.view');

        return view('admin.inventory.stores', ['stores' => InventoryStore::with('locations')->where('organization_id', $this->organizationId())->orderBy('name')->get(), 'kinds' => config('inventory.store_kinds'), 'departments' => $this->departmentChoices()]);
    }

    public function storeStore(Request $request): RedirectResponse
    {
        $this->allow($request, 'inventory.store.manage');
        $v = $request->validate(['code' => ['required', 'string', 'max:30'], 'name' => ['required', 'string', 'max:120'], 'kind' => ['required', Rule::in(config('inventory.store_kinds'))], 'department_id' => ['nullable', 'integer'], 'allow_negative' => ['nullable', 'boolean']]);
        $v['branch_id'] = $this->tenant->getBranchId();
        $v['allow_negative'] = $request->boolean('allow_negative');
        if (! empty($v['department_id'])) {
            $this->findDepartment((int) $v['department_id']);
        }
        $this->masters->createStore($this->organizationId(), $v);

        return back()->with('success', __('Store added.'));
    }

    public function addLocation(Request $request, InventoryStore $store): RedirectResponse
    {
        $this->scoped($request, $store, 'inventory.store.manage');
        $v = $request->validate(['code' => ['required', 'string', 'max:30'], 'name' => ['required', 'string', 'max:120']]);
        $this->masters->addLocation($store, $v['code'], $v['name']);

        return back()->with('success', __('Location added.'));
    }

    public function stock(Request $request)
    {
        $this->allow($request, 'inventory.stock.view');
        $org = $this->organizationId();
        $rows = InventoryStock::query()->with(['item:id,code,name,base_uom', 'store:id,name', 'batch:id,batch_no,expiry_date,status'])->where('organization_id', $org)->where('quantity_on_hand', '!=', 0)
            ->when($request->query('store'), fn ($q, $v) => $q->whereHas('store', fn ($s) => $s->where('public_id', $v)))
            ->when($request->query('q'), fn ($q, $v) => $q->whereHas('item', fn ($i) => $i->where('code', 'like', "%$v%")->orWhere('name', 'like', "%$v%")))
            ->orderBy('store_id')->orderBy('item_id')->paginate(40)->withQueryString();

        return view('admin.inventory.stock', ['rows' => $rows, 'stores' => $this->storeChoices(true)]);
    }

    public function reports(Request $request)
    {
        $this->allow($request, 'inventory.report.view');
        $org = $this->organizationId();
        $report = in_array($request->query('report'), ['valuation', 'consumption', 'slow', 'suppliers'], true) ? $request->query('report') : 'valuation';
        $days = in_array((int) $request->query('days'), [30, 90, 180], true) ? (int) $request->query('days') : 30;
        $to = CarbonImmutable::now();
        $from = $to->subDays($days);
        $data = match ($report) {
            'valuation' => $this->reports->valuation($org),
            'consumption' => $this->reports->consumptionByDepartment($org, $from, $to),
            'slow' => $this->reports->slowMovers($org, $days < 90 ? 90 : $days),
            default => $this->reports->supplierPerformance($org, $from, $to),
        };
        if ($request->query('export') === 'csv') {
            $this->allow($request, 'inventory.report.export');

            return response()->streamDownload(function () use ($data) {
                $out = fopen('php://output', 'w');
                $first = $data->first();
                $first && fputcsv($out, array_keys((array) $first));
                foreach ($data as $row) {
                    fputcsv($out, array_map(fn ($v) => is_string($v) && preg_match('/^[=+\-@]/', $v) ? "'".$v : $v, (array) $row));
                }
                fclose($out);
            }, "inventory-$report.csv", ['Content-Type' => 'text/csv']);
        }

        return view('admin.inventory.reports', ['report' => $report, 'days' => $days, 'data' => $data, 'rejections' => $report === 'suppliers' ? $this->reports->rejectionRate($org) : collect()]);
    }
}
