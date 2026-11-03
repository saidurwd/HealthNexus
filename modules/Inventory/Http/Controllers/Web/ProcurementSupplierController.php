<?php

namespace Modules\Inventory\Http\Controllers\Web;

use App\Models\Inventory\ProcurementBudget;
use App\Models\Inventory\ProcurementContract;
use App\Models\Inventory\ProcurementSupplier;
use App\Services\Inventory\BudgetService;
use App\Services\Inventory\SupplierService;
use App\Services\TenantContextResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Suppliers (qualification, documents, scorecard), price contracts and department budgets. */
class ProcurementSupplierController extends InventoryController
{
    public function __construct(TenantContextResolver $tenant, private readonly SupplierService $suppliers, private readonly BudgetService $budgets)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'procurement.supplier.view');
        $rows = ProcurementSupplier::query()->where('organization_id', $this->organizationId())
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))->when($request->query('q'), fn ($q, $v) => $q->where(fn ($w) => $w->where('name', 'like', "%$v%")->orWhere('code', 'like', "%$v%")))
            ->orderBy('name')->paginate(25)->withQueryString();

        return view('admin.procurement.suppliers', ['rows' => $rows]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->allow($request, 'procurement.supplier.manage');
        $v = $request->validate(['code' => ['required', 'string', 'max:30'], 'name' => ['required', 'string', 'max:200'], 'contact_name' => ['nullable', 'string', 'max:120'], 'phone' => ['nullable', 'string', 'max:40'], 'email' => ['nullable', 'email', 'max:150'], 'bin_number' => ['nullable', 'string', 'max:40'], 'payment_terms_days' => ['nullable', 'integer', 'min:0', 'max:365']]);
        $supplier = $this->suppliers->create($this->organizationId(), array_filter($v, fn ($x) => $x !== null));

        return redirect()->route('admin.procurement.suppliers.show', $supplier)->with('success', __('Supplier added. Add its documents, then qualify it.'));
    }

    public function show(Request $request, ProcurementSupplier $supplier)
    {
        $this->scoped($request, $supplier, 'procurement.supplier.view');

        return view('admin.procurement.supplier', [
            'supplier' => $supplier, 'documents' => $supplier->documents()->orderBy('doc_type')->orderByDesc('expires_on')->get(), 'evaluations' => $supplier->evaluations()->latest('period_end')->limit(8)->get(), 'gaps' => $this->suppliers->documentGaps($supplier),
            'contracts' => ProcurementContract::with('item:id,code,name')->where('supplier_id', $supplier->id)->latest('valid_from')->limit(20)->get(), 'types' => config('inventory.supplier_document_types'), 'items' => $this->itemChoices(),
        ]);
    }

    public function document(Request $request, ProcurementSupplier $supplier): RedirectResponse
    {
        $this->scoped($request, $supplier, 'procurement.supplier.manage');
        $v = $request->validate(['doc_type' => ['required', Rule::in(config('inventory.supplier_document_types'))], 'number' => ['nullable', 'string', 'max:80'], 'issued_on' => ['nullable', 'date'], 'expires_on' => ['nullable', 'date']]);
        $this->suppliers->addDocument($supplier, $v['doc_type'], $v['number'] ?? null, $v['issued_on'] ?? null, $v['expires_on'] ?? null);

        return back()->with('success', __('Document added.'));
    }

    public function status(Request $request, ProcurementSupplier $supplier): RedirectResponse
    {
        $action = $request->input('action');
        if ($action === 'qualify') {
            $this->scoped($request, $supplier, 'procurement.supplier.qualify');
            $this->suppliers->qualify($supplier, $request->user());

            return back()->with('success', __('Supplier qualified.'));
        }
        $this->scoped($request, $supplier, 'procurement.supplier.qualify');
        $v = $request->validate(['reason' => ['required', 'string', 'max:300']]);
        $action === 'disqualify' ? $this->suppliers->disqualify($supplier, $v['reason'], $request->user()) : $this->suppliers->suspend($supplier, $v['reason']);

        return back()->with('warning', __('Supplier status changed.'));
    }

    public function evaluate(Request $request, ProcurementSupplier $supplier): RedirectResponse
    {
        $this->scoped($request, $supplier, 'procurement.supplier.manage');
        $v = $request->validate(['delivery' => ['required', 'numeric', 'between:0,5'], 'quality' => ['required', 'numeric', 'between:0,5'], 'price' => ['required', 'numeric', 'between:0,5'], 'notes' => ['nullable', 'string', 'max:300']]);
        $this->suppliers->evaluate($supplier, $request->user(), (float) $v['delivery'], (float) $v['quality'], (float) $v['price'], $v['notes'] ?? null);

        return back()->with('success', __('Scorecard saved.'));
    }

    public function contract(Request $request, ProcurementSupplier $supplier): RedirectResponse
    {
        $this->scoped($request, $supplier, 'procurement.contract.manage');
        $v = $request->validate(['item' => ['required', 'string'], 'unit_price' => ['required', 'numeric', 'gt:0'], 'valid_from' => ['required', 'date'], 'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'], 'reference' => ['nullable', 'string', 'max:80']]);
        ProcurementContract::create(['organization_id' => $this->organizationId(), 'supplier_id' => $supplier->id, 'item_id' => $this->findItem($v['item'])->id, 'unit_price' => $v['unit_price'], 'valid_from' => $v['valid_from'], 'valid_to' => $v['valid_to'] ?? null, 'reference' => $v['reference'] ?? null]);

        return back()->with('success', __('Contract price saved.'));
    }

    public function budgets(Request $request)
    {
        $this->allow($request, 'procurement.budget.view');
        $org = $this->organizationId();
        $rows = ProcurementBudget::query()->where('organization_id', $org)->orderByDesc('period_start')->get()->map(function ($b) {
            $b->setAttribute('committed', $this->budgets->committed($b));

            return $b;
        });

        return view('admin.procurement.budgets', ['rows' => $rows, 'departments' => $this->departmentChoices()]);
    }

    public function storeBudget(Request $request): RedirectResponse
    {
        $this->allow($request, 'procurement.budget.manage');
        $v = $request->validate(['department_id' => ['required', 'integer'], 'period_start' => ['required', 'date'], 'period_end' => ['required', 'date', 'after:period_start'], 'amount' => ['required', 'numeric', 'gt:0']]);
        $this->budgets->set($this->organizationId(), $this->findDepartment((int) $v['department_id'])->id, $v['period_start'], $v['period_end'], (string) $v['amount']);

        return back()->with('success', __('Budget saved.'));
    }
}
