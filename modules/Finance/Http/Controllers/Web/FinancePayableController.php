<?php

namespace Modules\Finance\Http\Controllers\Web;

use App\Models\Finance\BankAccount;
use App\Models\Finance\Payable;
use App\Models\Finance\SupplierPayment;
use App\Models\Finance\SupplierPaymentLine;
use App\Models\Inventory\ProcurementSupplier;
use App\Services\Finance\PayableService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

/** What the hospital owes suppliers, and paying them with a second person's approval. */
class FinancePayableController extends FinanceController
{
    public function __construct(TenantContextResolver $tenant, private readonly PayableService $payables)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'finance.payable.view');
        $org = $this->organizationId();
        $age = $this->payables->ageing($org, $request->query('on'));
        if ($request->query('export') === 'csv') {
            return $this->csv($request, 'payable-ageing', array_merge(['Supplier'], $age['buckets'], ['Total']), collect($age['rows'])->map(fn ($r) => array_merge([$r['name']], $r['buckets'], [$r['total']])));
        }
        $open = Payable::query()->where(['organization_id' => $org, 'status' => 'open'])->orderBy('due_date')->limit(100)->get();

        return view('admin.finance.payables', ['age' => $age, 'open' => $open, 'suppliers' => ProcurementSupplier::query()->whereIn('id', $open->pluck('supplier_id'))->pluck('name', 'id'), 'svc' => $this->payables]);
    }

    public function payments(Request $request)
    {
        $this->allow($request, 'finance.payable.view');
        $org = $this->organizationId();
        $rows = SupplierPayment::query()->where('organization_id', $org)->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))->latest('id')->paginate(30)->withQueryString();

        return view('admin.finance.payments', ['rows' => $rows, 'suppliers' => ProcurementSupplier::query()->whereIn('id', $rows->pluck('supplier_id'))->pluck('name', 'id')]);
    }

    public function createPayment(Request $request)
    {
        $this->allow($request, 'finance.payable.manage');
        $org = $this->organizationId();
        $supplierId = $request->integer('supplier') ?: null;
        $open = Payable::query()->where(['organization_id' => $org, 'status' => 'open'])->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId))->orderBy('due_date')->get();

        return view('admin.finance.payment-form', ['open' => $open, 'suppliers' => ProcurementSupplier::query()->where('organization_id', $org)->orderBy('name')->get(['id', 'name']), 'supplierId' => $supplierId, 'banks' => BankAccount::query()->where(['organization_id' => $org, 'active' => true])->orderBy('name')->get(), 'svc' => $this->payables]);
    }

    public function storePayment(Request $request)
    {
        $this->allow($request, 'finance.payable.manage');
        $d = $request->validate(['supplier_id' => ['required', 'integer'], 'payment_date' => ['required', 'date'], 'method' => ['required', 'in:bank,cash,wallet,cheque'], 'bank_account_id' => ['required', 'integer'], 'withheld_ait' => ['nullable', 'numeric'], 'withheld_vat' => ['nullable', 'numeric'],
            'reference' => ['nullable', 'string', 'max:80'], 'note' => ['nullable', 'string', 'max:250'], 'pay' => ['nullable', 'array'], 'pay.*' => ['nullable', 'numeric']]);
        $allocations = collect($d['pay'] ?? [])->filter(fn ($v) => $v !== null && bccomp((string) $v, '0', 2) > 0)->map(fn ($v, $id) => ['payable_id' => (int) $id, 'amount' => (string) $v])->values()->all();
        $payment = $this->payables->createPayment($this->organizationId(), $d, $allocations, $request->user());

        return redirect()->route('admin.finance.payments.show', $payment->public_id)->with('success', __('Payment prepared; a second person must approve it.'));
    }

    public function showPayment(Request $request, string $payment)
    {
        $this->allow($request, 'finance.payable.view');
        $p = $this->find(SupplierPayment::class, $payment);

        return view('admin.finance.payment', ['p' => $p, 'supplier' => ProcurementSupplier::query()->find($p->supplier_id), 'bank' => BankAccount::query()->find($p->bank_account_id), 'lines' => SupplierPaymentLine::query()->where('payment_id', $p->id)->get(), 'payables' => Payable::query()->whereIn('id', SupplierPaymentLine::query()->where('payment_id', $p->id)->pluck('payable_id'))->get()->keyBy('id')]);
    }

    public function act(Request $request, string $payment)
    {
        $p = $this->find(SupplierPayment::class, $payment);
        $d = $request->validate(['action' => ['required', 'in:approve,pay,cancel,reverse'], 'note' => ['nullable', 'string', 'max:250']]);
        $this->allow($request, ['approve' => 'finance.payable.approve', 'pay' => 'finance.payable.pay', 'cancel' => 'finance.payable.manage', 'reverse' => 'finance.journal.reverse'][$d['action']]);
        match ($d['action']) {
            'approve' => $this->payables->approvePayment($p, $request->user()),
            'pay' => $this->payables->pay($p, $request->user()),
            'cancel' => $this->payables->cancelPayment($p, (string) ($d['note'] ?? '')),
            'reverse' => $this->payables->reversePayment($p, $request->user(), (string) ($d['note'] ?? '')),
            default => abort(422),
        };

        return back()->with('success', __('Done.'));
    }
}
