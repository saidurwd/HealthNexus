<?php

namespace App\Services\Billing;

use App\Support\Dashboard\DashboardPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * The figures of the billing dashboard. In the period (and the period before): billed, collected, collected as a share of billed, discounts given, refunds paid, bills raised and payments taken, and collections by
 * how the money came (cash, card, mobile banking, bank). Now: what patients and corporates still owe on finalized bills, by how old the bill is, and the cashier sessions still open. Totals only: no patient is named.
 */
class BillingOverview
{
    /** @var array<string,string> */
    private const METHODS = ['cash' => 'Cash', 'card' => 'Card', 'mobile_financial_service' => 'Mobile banking', 'bank_transfer' => 'Bank transfer'];

    /** The bill or payment date falls on a day of the period (a date column or a datetime column alike). */
    private function days(Builder $q, string $column, DashboardPeriod $p): void
    {
        $q->where($column, '>=', $p->fromDate())->where($column, '<', $p->to->addDay()->toDateString());
    }

    /** @return array<string,mixed> */
    public function summary(int $organizationId, ?int $branchId, DashboardPeriod $period, ?CarbonImmutable $now = null): array
    {
        $now = $now ?? CarbonImmutable::now();
        $invoices = fn () => DB::table('billing_invoices')->where('billing_invoices.organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('billing_invoices.branch_id', $branchId))->whereIn('billing_invoices.status', ['finalized', 'partially_paid', 'paid']);
        $payments = fn () => DB::table('billing_payments')->where('billing_payments.organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('billing_payments.branch_id', $branchId))->where('billing_payments.status', 'completed');
        $in = function (DashboardPeriod $p) use ($invoices, $payments, $organizationId, $branchId) {
            $billed = (float) $invoices()->where(fn ($w) => $this->days($w, 'invoice_date', $p))->sum('grand_total');
            $collected = (float) $payments()->where(fn ($w) => $this->days($w, 'payment_date', $p))->sum('amount');

            return [
                'billed' => $billed, 'collected' => $collected, 'collection_rate' => $billed > 0 ? round(100 * $collected / $billed, 1) : null,
                'discounts' => (float) $invoices()->where(fn ($w) => $this->days($w, 'invoice_date', $p))->sum('discount_amount'),
                'refunds' => (float) DB::table('billing_refunds')->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->where('status', 'processed')->whereBetween('processed_at', [$p->fromUtc(), $p->toUtc()])->sum('amount'),
                'invoices' => (int) $invoices()->where(fn ($w) => $this->days($w, 'invoice_date', $p))->count(), 'payments' => (int) $payments()->where(fn ($w) => $this->days($w, 'payment_date', $p))->count(),
            ];
        };
        $owing = fn () => $invoices()->whereIn('billing_invoices.status', ['finalized', 'partially_paid'])->where('due_amount', '>', 0);
        $today = $now->setTimezone((string) config('ux.timezone', 'Asia/Dhaka'))->startOfDay();
        $aging = collect([['0–30 days', 0, 30], ['31–60 days', 31, 60], ['61–90 days', 61, 90], ['Over 90 days', 91, null]])->map(function ($b) use ($owing, $today) {
            $q = $owing();
            $newest = $today->subDays($b[1])->addDay()->toDateString();
            $q->where('invoice_date', '<', $newest);
            $b[2] !== null && $q->where('invoice_date', '>=', $today->subDays($b[2])->toDateString());

            return ['age' => $b[0], 'owed' => round((float) $q->sum('due_amount'), 2)];
        })->all();
        $methods = $payments()->join('billing_payment_methods as m', 'm.id', '=', 'billing_payments.payment_method_id')->where(fn ($w) => $this->days($w, 'payment_date', $period))->selectRaw('m.type as type, sum(billing_payments.amount) as total')->groupBy('m.type')->pluck('total', 'type');
        $perDay = $invoices()->where(fn ($w) => $this->days($w, 'invoice_date', $period))->selectRaw('date(invoice_date) as d, sum(grand_total) as total')->groupBy(DB::raw('date(invoice_date)'))->pluck('total', 'd');
        $paidPerDay = $payments()->where(fn ($w) => $this->days($w, 'payment_date', $period))->selectRaw('date(payment_date) as d, sum(billing_payments.amount) as total')->groupBy(DB::raw('date(payment_date)'))->pluck('total', 'd');

        return [
            'current' => $in($period), 'previous' => $in($period->previous()),
            'owed' => round((float) $owing()->sum('due_amount'), 2), 'owing_bills' => (int) $owing()->count(), 'aging' => $aging,
            'corporate_owed' => round((float) $owing()->whereNotNull('corporate_id')->sum('due_amount'), 2),
            'open_sessions' => (int) DB::table('billing_cashier_sessions')->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->where('status', 'open')->count(),
            'by_method' => collect(self::METHODS)->map(fn ($label, $k) => ['method' => $label, 'taka' => round((float) ($methods[$k] ?? 0), 2)])->values()->all(),
            'per_day' => collect($period->days() > 0 ? range(0, $period->days() - 1) : [])->map(function ($i) use ($period, $perDay, $paidPerDay) {
                $d = $period->from->addDays($i)->toDateString();

                return ['day' => CarbonImmutable::parse($d)->format('d M'), 'billed' => round((float) ($perDay[$d] ?? 0), 2), 'collected' => round((float) ($paidPerDay[$d] ?? 0), 2)];
            })->all(),
        ];
    }
}
