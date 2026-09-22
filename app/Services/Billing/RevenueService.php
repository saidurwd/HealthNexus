<?php

namespace App\Services\Billing;

use App\Models\Billing\BillingCashierSession;
use App\Models\Billing\BillingInvoice;
use App\Models\Billing\BillingPayment;
use App\Models\Billing\BillingRefund;
use Illuminate\Support\Carbon;

/**
 * Read-only aggregation queries backing the Billing Dashboard and Reports screens. These are
 * display-only figures (not written back anywhere), so plain SQL SUM aggregates are fine here —
 * unlike AdvanceService::balance(), which is an authoritative financial figure and must stay
 * bcmath-derived from the ledger.
 */
class RevenueService
{
    public function todaysSummary(int $companyId, ?int $branchId = null): array
    {
        $today = Carbon::today()->toDateString();

        $invoices = BillingInvoice::query()->forTenant($companyId, $branchId)->where('invoice_date', $today);
        $payments = BillingPayment::query()->forTenant($companyId, $branchId)->where('payment_date', $today)->where('status', 'completed');
        $refunds = BillingRefund::query()->forTenant($companyId, $branchId)->whereDate('processed_at', $today)->where('status', 'processed');

        return [
            'total_billed' => (string) $invoices->clone()->sum('grand_total'),
            'total_collected' => (string) $payments->clone()->sum('amount'),
            'total_due' => (string) BillingInvoice::query()->forTenant($companyId, $branchId)->whereIn('status', ['finalized', 'partially_paid'])->sum('due_amount'),
            'total_refunds' => (string) $refunds->clone()->sum('amount'),
            'total_discounts' => (string) $invoices->clone()->sum('discount_amount'),
            'invoice_count' => $invoices->clone()->count(),
            'payment_count' => $payments->clone()->count(),
        ];
    }

    public function cashierSummary(int $companyId, ?int $branchId = null): array
    {
        $openSessions = BillingCashierSession::query()->forTenant($companyId, $branchId)->where('status', 'open')->count();

        $today = Carbon::today()->toDateString();

        $byType = BillingPayment::query()
            ->forTenant($companyId, $branchId)
            ->where('status', 'completed')
            ->where('payment_date', $today)
            ->join('billing_payment_methods', 'billing_payment_methods.id', '=', 'billing_payments.payment_method_id')
            ->selectRaw('billing_payment_methods.type as method_type, SUM(billing_payments.amount) as total')
            ->groupBy('billing_payment_methods.type')
            ->pluck('total', 'method_type');

        return [
            'open_sessions' => $openSessions,
            'cash_collection' => (string) ($byType['cash'] ?? '0.00'),
            'card_collection' => (string) ($byType['card'] ?? '0.00'),
            'mfs_collection' => (string) ($byType['mobile_financial_service'] ?? '0.00'),
            'bank_collection' => (string) ($byType['bank_transfer'] ?? '0.00'),
        ];
    }

    public function revenueByCategory(int $companyId, ?int $branchId, string $from, string $to): \Illuminate\Support\Collection
    {
        return \App\Models\Billing\BillingInvoiceItem::query()
            ->join('billing_invoices', 'billing_invoices.id', '=', 'billing_invoice_items.invoice_id')
            ->join('billing_items', 'billing_items.id', '=', 'billing_invoice_items.billing_item_id')
            ->join('billing_categories', 'billing_categories.id', '=', 'billing_items.category_id')
            ->where('billing_invoices.company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('billing_invoices.branch_id', $branchId))
            ->whereBetween('billing_invoices.invoice_date', [$from, $to])
            ->whereIn('billing_invoices.status', ['finalized', 'partially_paid', 'paid'])
            ->selectRaw('billing_categories.name as category, SUM(billing_invoice_items.net_amount) as revenue')
            ->groupBy('billing_categories.name')
            ->orderByDesc('revenue')
            ->get();
    }

    public function outstandingByPatient(int $companyId, ?int $branchId = null)
    {
        return BillingInvoice::query()
            ->forTenant($companyId, $branchId)
            ->whereIn('status', ['finalized', 'partially_paid'])
            ->where('due_amount', '>', 0)
            ->with('patient:id,first_name,last_name,enterprise_patient_no')
            ->orderByDesc('due_amount')
            ->get(['id', 'patient_id', 'invoice_number', 'invoice_date', 'grand_total', 'paid_amount', 'due_amount']);
    }

    public function outstandingByCorporate(int $companyId, ?int $branchId = null)
    {
        return BillingInvoice::query()
            ->forTenant($companyId, $branchId)
            ->whereNotNull('corporate_id')
            ->whereIn('status', ['finalized', 'partially_paid'])
            ->where('due_amount', '>', 0)
            ->selectRaw('corporate_id, COUNT(*) as invoice_count, SUM(due_amount) as total_due')
            ->groupBy('corporate_id')
            ->with('corporate:id,name')
            ->orderByDesc('total_due')
            ->get();
    }

    public function discountSummary(int $companyId, ?int $branchId, string $from, string $to)
    {
        return BillingInvoice::query()
            ->forTenant($companyId, $branchId)
            ->whereBetween('invoice_date', [$from, $to])
            ->where('discount_amount', '>', 0)
            ->orderByDesc('discount_amount')
            ->get(['id', 'invoice_number', 'patient_id', 'invoice_date', 'discount_type', 'discount_amount']);
    }

    public function refundSummary(int $companyId, ?int $branchId, string $from, string $to)
    {
        return BillingRefund::query()
            ->forTenant($companyId, $branchId)
            ->whereBetween('requested_at', [$from, $to])
            ->orderByDesc('requested_at')
            ->get();
    }
}
