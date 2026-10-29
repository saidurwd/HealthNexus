<?php

namespace App\Services\Finance;

use App\Models\Billing\BillingAdjustment;
use App\Models\Billing\BillingInvoice;
use App\Models\Billing\BillingPayment;
use App\Models\Billing\BillingRefund;
use App\Models\Finance\Account;
use App\Models\Finance\Posting;
use Illuminate\Support\Facades\DB;

/** Does the ledger agree with Billing? Every row is "what Billing says" against "what the books say"; a difference other than zero is something to look at. */
class ReconciliationService
{
    public function __construct(private readonly LedgerService $ledger) {}

    /**
     * @return array{rows:list<array{label:string,billing:string,ledger:string,difference:string}>,unposted:array<string,int>,in_balance:bool}
     */
    public function billingVsLedger(int $organizationId, string $from, string $to): array
    {
        $posted = fn (string $event, string $side) => (string) (DB::table('fin_journals as j')->join('fin_journal_lines as l', 'l.journal_id', '=', 'j.id')->join('fin_accounts as a', 'a.id', '=', 'l.account_id')
            ->where('j.organization_id', $organizationId)->where('j.status', 'posted')->where('j.event_key', $event)->whereBetween('j.journal_date', [$from, $to])->whereIn('a.control', ['receivable', 'cash', 'bank'])->sum($side === 'debit' ? 'l.debit' : 'l.credit'));
        $invoices = BillingInvoice::query()->where('organization_id', $organizationId)->whereNotNull('finalized_at')->whereNotIn('status', ['draft', 'cancelled'])->whereBetween(DB::raw('date(finalized_at)'), [$from, $to]);
        $billed = (string) $invoices->clone()->sum('grand_total');
        $cancelledBilled = (string) BillingInvoice::query()->where('organization_id', $organizationId)->where('status', 'cancelled')->whereNotNull('finalized_at')->whereBetween(DB::raw('date(finalized_at)'), [$from, $to])->whereExists(fn ($q) => $q->selectRaw('1')->from('fin_journals as j')->where('j.organization_id', $organizationId)->where('j.source_type', 'invoice')->where('j.event_key', 'invoice.finalized')->whereColumn('j.source_id', DB::raw('billing_invoices.id')))->sum('grand_total');
        $invoiceLedger = (string) DB::table('fin_journals as j')->join('fin_journal_lines as l', 'l.journal_id', '=', 'j.id')->join('fin_accounts as a', 'a.id', '=', 'l.account_id')->where('j.organization_id', $organizationId)->where('j.status', 'posted')
            ->where('j.source_type', 'invoice')->where('j.event_key', 'invoice.finalized')->whereBetween('j.journal_date', [$from, $to])->where('a.control', 'receivable')->sum('l.debit');
        $payments = (string) BillingPayment::query()->where('organization_id', $organizationId)->whereIn('status', ['completed', 'refunded'])->whereNotNull('invoice_id')->whereBetween('payment_date', [$from, $to])->sum('amount');
        $payLedger = (string) DB::table('fin_journals as j')->join('fin_journal_lines as l', 'l.journal_id', '=', 'j.id')->join('fin_accounts as a', 'a.id', '=', 'l.account_id')->where('j.organization_id', $organizationId)->where('j.status', 'posted')
            ->where('j.source_type', 'payment')->whereBetween('j.journal_date', [$from, $to])->where('a.control', 'receivable')->sum('l.credit');
        $refunds = (string) BillingRefund::query()->where('organization_id', $organizationId)->where('status', 'processed')->whereNotNull('invoice_id')->whereBetween(DB::raw('date(processed_at)'), [$from, $to])->sum('amount');
        $refundLedger = (string) DB::table('fin_journals as j')->join('fin_journal_lines as l', 'l.journal_id', '=', 'j.id')->join('fin_accounts as a', 'a.id', '=', 'l.account_id')->where('j.organization_id', $organizationId)->where('j.status', 'posted')
            ->where('j.source_type', 'refund')->whereBetween('j.journal_date', [$from, $to])->where('a.control', 'receivable')->sum('l.debit');
        $arControl = '0.00';
        foreach (Account::query()->where(['organization_id' => $organizationId, 'control' => 'receivable'])->get() as $acc) {
            $arControl = bcadd($arControl, $this->ledger->balance($organizationId, $acc), 2);
        }
        $items = (string) DB::table('fin_receivables')->where('organization_id', $organizationId)->selectRaw("coalesce(sum(case when status = 'cancelled' then 0 - settled - written_off else amount - settled - written_off end), 0) as left_")->value('left_');

        $row = fn (string $label, string $billing, string $ledger) => ['label' => $label, 'billing' => bcadd($billing, '0', 2), 'ledger' => bcadd($ledger, '0', 2), 'difference' => bcsub($billing, $ledger, 2)];
        $rows = [
            $row('Invoices finalised (total billed)', bcadd($billed, $cancelledBilled, 2), $invoiceLedger),
            $row('Payments received on invoices', $payments, $payLedger),
            $row('Refunds paid', $refunds, $refundLedger),
            $row('Receivable items left open vs receivable control accounts (all postings)', $items, $arControl),
        ];
        $unposted = [
            'failed' => Posting::query()->where(['organization_id' => $organizationId, 'status' => 'failed'])->count(),
            'invoices_not_posted' => (clone $invoices)->whereNotExists(fn ($q) => $q->selectRaw('1')->from('fin_journals as j')->where('j.organization_id', $organizationId)->where('j.source_type', 'invoice')->where('j.event_key', 'invoice.finalized')->whereColumn('j.source_id', DB::raw('billing_invoices.id')))->count(),
            'adjustments_not_posted' => BillingAdjustment::query()->where('organization_id', $organizationId)->where('status', 'approved')->whereBetween(DB::raw('date(approved_at)'), [$from, $to])->whereNotExists(fn ($q) => $q->selectRaw('1')->from('fin_postings as p')->where('p.organization_id', $organizationId)->where('p.source_type', 'adjustment')->whereColumn('p.source_id', DB::raw('billing_adjustments.id'))->whereIn('p.status', ['posted', 'skipped']))->count(),
        ];

        return ['rows' => $rows, 'unposted' => $unposted, 'in_balance' => collect($rows)->every(fn ($r) => bccomp($r['difference'], '0', 2) === 0) && $unposted['failed'] === 0 && $unposted['invoices_not_posted'] === 0];
    }

    /**
     * Parties for whom the receivable control accounts and the open items disagree (the ledger is by party on every receivable line).
     *
     * @return list<array{party_type:string,party_id:int,ledger:string,items:string,difference:string}>
     */
    public function receivableDifferences(int $organizationId, ?string $to = null, int $limit = 50): array
    {
        $ledger = DB::table('fin_journal_lines as l')->join('fin_journals as j', 'j.id', '=', 'l.journal_id')->join('fin_accounts as a', 'a.id', '=', 'l.account_id')->where('l.organization_id', $organizationId)->where('j.status', 'posted')->where('a.control', 'receivable')
            ->when($to, fn ($q) => $q->where('l.journal_date', '<=', $to))->groupBy('l.party_type', 'l.party_id')->selectRaw('l.party_type as t, l.party_id as id, coalesce(sum(l.debit - l.credit), 0) as v')->get()->mapWithKeys(fn ($r) => [$r->t.':'.$r->id => (string) $r->v]);
        $items = DB::table('fin_receivables')->where('organization_id', $organizationId)->groupBy('party_type', 'party_id')->selectRaw("party_type as t, party_id as id, coalesce(sum(case when status = 'cancelled' then 0 - settled - written_off else amount - settled - written_off end), 0) as v")->get()->mapWithKeys(fn ($r) => [$r->t.':'.$r->id => (string) $r->v]);
        $out = [];
        foreach ($ledger->keys()->merge($items->keys())->unique() as $key) {
            $l = bcadd($ledger[$key] ?? '0', '0', 2);
            $i = bcadd($items[$key] ?? '0', '0', 2);
            if (bccomp($l, $i, 2) !== 0) {
                [$type, $id] = explode(':', $key);
                $out[] = ['party_type' => $type, 'party_id' => (int) $id, 'ledger' => $l, 'items' => $i, 'difference' => bcsub($i, $l, 2)];
            }
        }

        return array_slice($out, 0, $limit);
    }
}
