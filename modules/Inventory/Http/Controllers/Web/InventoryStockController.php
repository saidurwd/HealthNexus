<?php

namespace Modules\Inventory\Http\Controllers\Web;

use App\Models\Inventory\InventoryAdjustment;
use App\Models\Inventory\InventoryBatch;
use App\Models\Inventory\InventoryCount;
use App\Models\Inventory\InventoryCountLine;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryRecall;
use App\Models\Inventory\InventoryStock;
use App\Services\Inventory\InventoryAdjustmentService;
use App\Services\Inventory\InventoryCountService;
use App\Services\Inventory\InventoryExpiryService;
use App\Services\TenantContextResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Expiry, holds and recalls; adjustments; stock counts. */
class InventoryStockController extends InventoryController
{
    public function __construct(TenantContextResolver $tenant, private readonly InventoryExpiryService $expiry, private readonly InventoryAdjustmentService $adjustments, private readonly InventoryCountService $counts)
    {
        parent::__construct($tenant);
    }

    public function expiry(Request $request)
    {
        $this->allow($request, 'inventory.expiry.view');
        $org = $this->organizationId();
        $days = in_array((int) $request->query('days'), [30, 90, 180, 365], true) ? (int) $request->query('days') : 90;

        return view('admin.inventory.expiry', [
            'days' => $days, 'risk' => $this->expiry->risk($org, $days),
            'held' => InventoryBatch::query()->with('item:id,code,name')->where('organization_id', $org)->whereIn('status', ['quarantined', 'recalled'])->latest('id')->limit(40)->get(),
            'recalls' => InventoryRecall::query()->with('item:id,code,name')->where('organization_id', $org)->latest('id')->limit(10)->get(), 'items' => $this->itemChoices(),
        ]);
    }

    public function hold(Request $request, InventoryBatch $batch): RedirectResponse
    {
        $this->scoped($request, $batch, 'inventory.expiry.manage');
        $v = $request->validate(['reason' => ['required', 'string', 'max:300']]);
        $moved = $this->expiry->quarantine($batch, $request->user(), $v['reason']);

        return back()->with('warning', __(':q moved to quarantine.', ['q' => $moved]));
    }

    public function release(Request $request, InventoryBatch $batch): RedirectResponse
    {
        $this->scoped($request, $batch, 'inventory.expiry.manage');
        $v = $request->validate(['note' => ['required', 'string', 'max:300']]);
        $this->expiry->release($batch, $request->user(), $v['note']);

        return back()->with('success', __('Batch released.'));
    }

    public function recall(Request $request): RedirectResponse
    {
        $this->allow($request, 'inventory.expiry.manage');
        $v = $request->validate(['item' => ['required', 'string'], 'batch_no' => ['nullable', 'string', 'max:60'], 'reason' => ['required', 'string', 'max:300']]);
        $recall = $this->expiry->recall($this->findItem($v['item']), $v['batch_no'] ?: null, $v['reason'], $request->user());

        return back()->with('warning', __('Recall done: :q unit(s) moved to quarantine.', ['q' => $recall->quantity_blocked]));
    }

    public function trace(Request $request, InventoryBatch $batch)
    {
        $this->scoped($request, $batch, 'inventory.expiry.view');

        return view('admin.inventory.trace', ['batch' => $batch->load('item'), 'trace' => $this->expiry->trace($batch)]);
    }

    public function adjustmentsIndex(Request $request)
    {
        $this->allow($request, 'inventory.adjustment.view');
        $org = $this->organizationId();
        $rows = InventoryAdjustment::query()->with(['item:id,code,name', 'store:id,name'])->where('organization_id', $org)->orderByRaw("status = 'requested' desc")->latest('id')->paginate(30);
        $positions = InventoryStock::query()->with(['item:id,public_id,code,name', 'store:id,public_id,name', 'batch:id,batch_no'])->where('organization_id', $org)->where('quantity_on_hand', '>', 0)->orderBy('store_id')->limit(300)->get();

        return view('admin.inventory.adjustments', ['rows' => $rows, 'positions' => $positions, 'reasons' => config('inventory.adjustment_reason_codes')]);
    }

    public function requestAdjustment(Request $request): RedirectResponse
    {
        $this->allow($request, 'inventory.adjustment.request');
        $v = $request->validate(['position' => ['required', 'integer'], 'quantity' => ['required', 'numeric', 'not_in:0'], 'reason_code' => ['required', Rule::in(config('inventory.adjustment_reason_codes'))], 'reason' => ['required', 'string', 'max:300']]);
        $position = InventoryStock::query()->where('organization_id', $this->organizationId())->findOrFail($v['position']);
        $this->adjustments->request($request->user(), $position->store, InventoryItem::findOrFail($position->item_id), (int) $position->batch_id, $v['quantity'], $v['reason_code'], $v['reason']);

        return back()->with('success', __('Adjustment sent for approval.'));
    }

    public function decideAdjustment(Request $request, InventoryAdjustment $adjustment): RedirectResponse
    {
        $this->scoped($request, $adjustment, 'inventory.adjustment.approve');
        if ($request->input('decision') === 'reject') {
            $v = $request->validate(['reason' => ['required', 'string', 'max:300']]);
            $this->adjustments->reject($adjustment, $request->user(), $v['reason']);

            return back()->with('warning', __('Adjustment rejected.'));
        }
        $this->adjustments->approve($adjustment, $request->user());

        return back()->with('success', __('Adjustment approved and posted.'));
    }

    public function countsIndex(Request $request)
    {
        $this->allow($request, 'inventory.count.view');

        return view('admin.inventory.counts', ['counts' => InventoryCount::query()->with('store:id,name')->where('organization_id', $this->organizationId())->latest('id')->paginate(20), 'stores' => $this->storeChoices()]);
    }

    public function startCount(Request $request): RedirectResponse
    {
        $this->allow($request, 'inventory.count.perform');
        $v = $request->validate(['store' => ['required', 'string'], 'category' => ['nullable', 'integer']]);
        $store = $this->findStore($v['store']);
        $itemIds = ! empty($v['category']) ? InventoryItem::where(['organization_id' => $this->organizationId(), 'category_id' => $v['category']])->pluck('id')->all() : null;
        $count = $this->counts->start($request->user(), $store, $itemIds);

        return redirect()->route('admin.inventory.counts.show', $count)->with('success', __('Count sheet ready.'));
    }

    public function showCount(Request $request, InventoryCount $count)
    {
        $this->scoped($request, $count, 'inventory.count.view');

        return view('admin.inventory.count', ['count' => $count->load('store'), 'lines' => $count->lines()->with('item:id,code,name,base_uom')->orderBy('id')->get(), 'batches' => InventoryBatch::whereIn('id', $count->lines()->pluck('batch_id'))->pluck('batch_no', 'id')]);
    }

    public function enterCount(Request $request, InventoryCount $count): RedirectResponse
    {
        $this->scoped($request, $count, 'inventory.count.perform');
        $v = $request->validate(['counted' => ['required', 'array'], 'counted.*' => ['nullable', 'numeric', 'min:0'], 'reason' => ['nullable', 'array'], 'reason.*' => ['nullable', 'string', 'max:200']]);
        foreach ($v['counted'] as $lineId => $qty) {
            if ($qty === null || $qty === '') {
                continue;
            }
            $line = InventoryCountLine::where('count_id', $count->id)->findOrFail($lineId);
            $this->counts->enter($line, $qty, $v['reason'][$lineId] ?? null);
        }

        return back()->with('success', __('Counts saved.'));
    }

    public function decideCount(Request $request, InventoryCount $count): RedirectResponse
    {
        $action = $request->input('action');
        if ($action === 'submit') {
            $this->scoped($request, $count, 'inventory.count.perform');
            $this->counts->submit($count, $request->user());

            return back()->with('success', __('Count submitted for approval.'));
        }
        $this->scoped($request, $count, 'inventory.count.approve');
        if ($action === 'reject') {
            $this->counts->reject($count, $request->user());

            return back()->with('warning', __('Count rejected.'));
        }
        $this->counts->approve($count, $request->user());

        return back()->with('success', __('Count approved; differences posted.'));
    }
}
