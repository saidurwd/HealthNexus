<?php

namespace Modules\Portal\Http\Controllers\Api\V1;

use App\Models\Billing\BillingInvoice;
use App\Models\Billing\BillingInvoiceItem;
use App\Models\Patient;
use App\Models\Portal\PortalPayment;
use App\Services\Portal\PortalAccess;
use App\Services\Portal\PortalAudit;
use App\Services\Portal\PortalException;
use App\Services\Portal\PortalPaymentService;
use App\Services\Portal\PortalPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Bills, online payment and receipts (S06). The money is posted by Billing only after the payment provider confirms it. */
class PortalMoneyApiController extends PortalApiController
{
    public function __construct(PortalAccess $access, private readonly PortalPaymentService $payments, private readonly PortalAudit $audit)
    {
        parent::__construct($access);
    }

    public function invoices(Request $request): JsonResponse
    {
        $rows = $this->payments->invoices($this->ids($request, 'billing'), $request->boolean('open'));

        return $this->ok($rows->map(fn ($i) => PortalPresenter::invoice($i))->values()->all(), 'OK', ['payments_enabled' => $this->payments->available()]);
    }

    public function invoice(Request $request, string $invoice): JsonResponse
    {
        $row = BillingInvoice::query()->whereIn('patient_id', $this->ids($request, 'billing'))->where('public_id', $invoice)->firstOrFail();
        $this->audit->access($this->account($request), $row->patient_id, 'view_invoice', $row);

        return $this->ok(PortalPresenter::invoice($row) + ['lines' => BillingInvoiceItem::query()->where('invoice_id', $row->id)->orderBy('id')->get()->map(fn (BillingInvoiceItem $l) => ['description' => $l->description, 'quantity' => (string) $l->quantity, 'amount' => number_format((float) $l->net_amount, 2, '.', '')])->all()]);
    }

    public function pay(Request $request, string $invoice): JsonResponse
    {
        $d = $request->validate(['method' => ['required', 'string', 'max:12'], 'amount' => ['nullable', 'numeric', 'min:0.01']]);
        $key = (string) $request->header('Idempotency-Key', '');
        if ($key === '') {
            throw new PortalException(__('An Idempotency-Key header is needed.'));
        }
        $row = BillingInvoice::query()->whereIn('patient_id', $this->ids($request, 'billing'))->where('public_id', $invoice)->firstOrFail();
        $payment = $this->payments->initiate($this->account($request), Patient::query()->findOrFail($row->patient_id), $row, $d['method'], $key, isset($d['amount']) ? (string) $d['amount'] : null);

        return $this->ok(PortalPresenter::payment($payment), __('Continue to the payment provider to finish.'), [], 201);
    }

    public function payment(Request $request, string $payment): JsonResponse
    {
        $row = PortalPayment::query()->whereIn('patient_id', $this->ids($request, 'billing'))->where('public_id', $payment)->firstOrFail();

        return $this->ok(PortalPresenter::payment($this->payments->confirm($row)));
    }

    public function receipts(Request $request): JsonResponse
    {
        $rows = DB::table('billing_receipts as r')->join('billing_payments as p', 'p.id', '=', 'r.payment_id')->whereIn('p.patient_id', $this->ids($request, 'billing'))->where('r.status', '!=', 'voided')
            ->orderByDesc('r.id')->limit(30)->get(['r.public_id', 'r.receipt_number', 'p.amount', 'p.payment_date']);

        return $this->ok($rows->map(fn ($r) => ['id' => $r->public_id, 'number' => $r->receipt_number, 'amount' => number_format((float) $r->amount, 2, '.', ''), 'date' => $r->payment_date])->all());
    }

    /** The payment provider's server calls this when a payment finishes. Never trusted: we ask the provider again. */
    public function callback(Request $request): JsonResponse
    {
        $d = $request->validate(['gateway_ref' => ['required', 'string', 'max:80']]);
        $payment = $this->payments->handleCallback($d['gateway_ref']);

        return response()->json(['success' => true, 'message' => 'OK', 'data' => ['status' => $payment?->status], 'meta' => []]);
    }
}
