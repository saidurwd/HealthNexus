<?php

namespace Modules\Inventory\Http\Controllers\Web;

use App\Models\Inventory\InventoryConsumption;
use App\Models\Inventory\InventoryIssue;
use App\Models\Inventory\InventoryIssueLine;
use App\Models\Inventory\InventoryRequest;
use App\Models\Inventory\InventoryTransfer;
use App\Models\Patient;
use App\Services\Inventory\InventoryConsumptionService;
use App\Services\Inventory\InventoryIssueService;
use App\Services\Inventory\InventoryTransferService;
use App\Services\TenantContextResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Transfers between stores, department requests and issues, and use at the point of care. */
class InventoryMovementController extends InventoryController
{
    public function __construct(TenantContextResolver $tenant, private readonly InventoryTransferService $transfers, private readonly InventoryIssueService $issues, private readonly InventoryConsumptionService $consumption)
    {
        parent::__construct($tenant);
    }

    public function transfersIndex(Request $request)
    {
        $this->allow($request, 'inventory.transfer.view');
        $rows = InventoryTransfer::query()->with(['fromStore:id,name', 'toStore:id,name', 'lines.item:id,code,name'])->where('organization_id', $this->organizationId())->orderByRaw("status = 'dispatched' desc")->latest('id')->paginate(25);

        return view('admin.inventory.transfers', ['rows' => $rows, 'stores' => $this->storeChoices(true), 'items' => $this->itemChoices()]);
    }

    public function requestTransfer(Request $request): RedirectResponse
    {
        $this->allow($request, 'inventory.transfer.create');
        $v = $request->validate(['from' => ['required', 'string'], 'to' => ['required', 'string'], 'item' => ['required', 'string'], 'quantity' => ['required', 'numeric', 'gt:0'], 'notes' => ['nullable', 'string', 'max:300']]);
        $this->transfers->request($request->user(), $this->findStore($v['from']), $this->findStore($v['to']), [['item_id' => $this->findItem($v['item'])->id, 'quantity' => $v['quantity']]], $v['notes'] ?? null);

        return back()->with('success', __('Transfer requested.'));
    }

    public function moveTransfer(Request $request, InventoryTransfer $transfer): RedirectResponse
    {
        $action = $request->input('action');
        if ($action === 'dispatch') {
            $this->scoped($request, $transfer, 'inventory.transfer.dispatch');
            $this->transfers->dispatch($transfer, $request->user());

            return back()->with('success', __('Dispatched: the stock is now in transit.'));
        }
        if ($action === 'receive') {
            $this->scoped($request, $transfer, 'inventory.transfer.receive');
            $received = collect((array) $request->input('received'))->filter(fn ($v) => $v !== null && $v !== '')->all();
            $this->transfers->receive($transfer, $request->user(), $received);

            return back()->with('success', __('Received into the store.'));
        }
        $this->scoped($request, $transfer, 'inventory.transfer.create');
        $this->transfers->cancel($transfer, $request->user());

        return back()->with('warning', __('Transfer cancelled.'));
    }

    public function requestsIndex(Request $request)
    {
        $this->allow($request, 'inventory.request.view');
        $rows = InventoryRequest::query()->with(['department:id,name', 'store:id,name', 'lines.item:id,code,name,base_uom'])->where('organization_id', $this->organizationId())->orderByRaw("status in ('submitted','partially_issued') desc")->latest('id')->paginate(25);
        $issues = InventoryIssue::query()->with(['department:id,name', 'lines.item:id,code,name'])->where('organization_id', $this->organizationId())->latest('id')->limit(10)->get();

        return view('admin.inventory.requests', ['rows' => $rows, 'issues' => $issues, 'stores' => $this->storeChoices(), 'departments' => $this->departmentChoices(), 'items' => $this->itemChoices()]);
    }

    public function submitRequest(Request $request): RedirectResponse
    {
        $this->allow($request, 'inventory.request.create');
        $v = $request->validate(['department_id' => ['required', 'integer'], 'store' => ['required', 'string'], 'item' => ['required', 'string'], 'quantity' => ['required', 'numeric', 'gt:0'], 'notes' => ['nullable', 'string', 'max:300']]);
        $this->issues->request($request->user(), $this->findDepartment((int) $v['department_id']), $this->findStore($v['store']), [['item_id' => $this->findItem($v['item'])->id, 'quantity' => $v['quantity']]], $v['notes'] ?? null);

        return back()->with('success', __('Request sent to the store.'));
    }

    public function decideRequest(Request $request, InventoryRequest $inventoryRequest): RedirectResponse
    {
        $this->scoped($request, $inventoryRequest, 'inventory.request.issue');
        if ($request->input('action') === 'reject') {
            $v = $request->validate(['reason' => ['required', 'string', 'max:300']]);
            $this->issues->reject($inventoryRequest, $v['reason']);

            return back()->with('warning', __('Request rejected.'));
        }
        $this->issues->issueRequest($inventoryRequest, $request->user());

        return back()->with('success', __('Issued.'));
    }

    public function issueDirect(Request $request): RedirectResponse
    {
        $this->allow($request, 'inventory.request.issue');
        $v = $request->validate(['store' => ['required', 'string'], 'department_id' => ['required', 'integer'], 'item' => ['required', 'string'], 'quantity' => ['required', 'numeric', 'gt:0']]);
        $this->issues->issueDirect($request->user(), $this->findStore($v['store']), $this->findDepartment((int) $v['department_id']), [['item_id' => $this->findItem($v['item'])->id, 'quantity' => $v['quantity']]]);

        return back()->with('success', __('Issued.'));
    }

    public function returnIssued(Request $request, InventoryIssueLine $line): RedirectResponse
    {
        $issue = InventoryIssue::findOrFail($line->issue_id);
        $this->scoped($request, $issue, 'inventory.request.issue');
        $v = $request->validate(['quantity' => ['required', 'numeric', 'gt:0'], 'reason' => ['required', 'string', 'max:200']]);
        $this->issues->returnLine($line, $request->user(), $v['quantity'], $v['reason']);

        return back()->with('success', __('Returned to the store.'));
    }

    public function consumptionIndex(Request $request)
    {
        $this->allow($request, 'inventory.consumption.record');
        $rows = InventoryConsumption::query()->with(['item:id,code,name,base_uom'])->where('organization_id', $this->organizationId())->latest('id')->paginate(25);

        return view('admin.inventory.consumption', ['rows' => $rows, 'stores' => $this->storeChoices(), 'departments' => $this->departmentChoices(), 'items' => $this->itemChoices()]);
    }

    public function recordConsumption(Request $request): RedirectResponse
    {
        $this->allow($request, 'inventory.consumption.record');
        $v = $request->validate(['store' => ['required', 'string'], 'item' => ['required', 'string'], 'quantity' => ['required', 'numeric', 'gt:0'], 'department_id' => ['nullable', 'integer'], 'patient' => ['nullable', 'string', 'max:40'], 'admission_id' => ['nullable', 'integer']]);
        $context = ['department_id' => isset($v['department_id']) ? $this->findDepartment((int) $v['department_id'])->id : null, 'source_module' => 'inventory', 'admission_id' => $v['admission_id'] ?? null];
        if (! empty($v['patient'])) {
            $patient = Patient::query()->where('organization_id', $this->organizationId())->where(fn ($q) => $q->where('public_id', $v['patient'])->orWhere('enterprise_patient_no', $v['patient']))->firstOrFail();
            $context['patient_id'] = $patient->id;
        }
        $this->consumption->record($request->user(), $this->findStore($v['store']), $this->findItem($v['item']), $v['quantity'], $context);

        return back()->with('success', __('Use recorded.'));
    }
}
