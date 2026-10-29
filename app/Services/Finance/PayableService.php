<?php

namespace App\Services\Finance;

use App\Models\Finance\BankAccount;
use App\Models\Finance\Journal;
use App\Models\Finance\Payable;
use App\Models\Finance\SupplierPayment;
use App\Models\Finance\SupplierPaymentLine;
use App\Models\Inventory\ProcurementSupplier;
use App\Models\Inventory\ProcurementSupplierInvoice;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Numbering\NumberSeriesService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** What the hospital owes suppliers: a payable for every approved supplier invoice, and payments that clear them (with tax withheld at source when it applies). */
class PayableService
{
    public function __construct(
        private readonly ChartService $chart,
        private readonly JournalService $journals,
        private readonly TaxRegister $tax,
        private readonly FinancePolicy $policy,
        private readonly NumberSeriesService $numbers,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * An invoice the purchasing side matched and approved becomes a payable: Dr goods received-not-invoiced (cost), Dr VAT input (tax), Cr suppliers (total).
     * Posting the same invoice again returns the same payable.
     */
    public function fromSupplierInvoice(ProcurementSupplierInvoice $invoice): Payable
    {
        $org = $invoice->organization_id;
        $existing = Payable::query()->where(['organization_id' => $org, 'source_type' => 'supplier_invoice', 'source_id' => $invoice->id])->first();
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($invoice, $org) {
            $tax = (string) $invoice->tax_amount;
            $total = (string) $invoice->total;
            $cost = bcsub($total, $tax, 2);
            $date = $invoice->invoice_date->toDateString();
            $lines = [['account' => $this->chart->accountFor($org, 'grni')->id, 'debit' => $cost, 'credit' => '0']];
            bccomp($tax, '0', 2) > 0 && $lines[] = ['account' => $this->chart->accountFor($org, 'vat_input')->id, 'debit' => $tax, 'credit' => '0'];
            $lines[] = ['account' => $this->chart->accountFor($org, 'ap')->id, 'debit' => '0', 'credit' => $total, 'party_type' => 'supplier', 'party_id' => $invoice->supplier_id, 'memo' => 'Invoice '.$invoice->invoice_number];
            $journal = $this->journals->postAuto($org, ['date' => $date, 'memo' => 'Supplier invoice '.$invoice->invoice_number, 'reference' => $invoice->invoice_number, 'source_type' => 'supplier_invoice', 'source_id' => (string) $invoice->id, 'event_key' => 'approved'], $lines, null,
                $this->policy->bool('period.post_into_locked', false));
            $supplier = ProcurementSupplier::query()->find($invoice->supplier_id);
            $this->tax->record($org, 'vat_input', $date, 'supplier_invoice', $invoice->id, $cost, $tax, $journal, $invoice->invoice_number, 'supplier', $invoice->supplier_id, $supplier?->name, $supplier?->bin_number);
            $terms = (int) ($supplier->payment_terms_days ?? $this->policy->int('payable.default_terms_days', 30));

            return Payable::create(['organization_id' => $org, 'supplier_id' => $invoice->supplier_id, 'source_type' => 'supplier_invoice', 'source_id' => $invoice->id, 'invoice_number' => $invoice->invoice_number, 'invoice_date' => $date,
                'due_date' => $invoice->due_on?->toDateString() ?? CarbonImmutable::parse($date)->addDays($terms)->toDateString(), 'amount' => $total]);
        });
    }

    public function outstanding(Payable $p): string
    {
        return bcsub((string) $p->amount, (string) $p->settled, 2);
    }

    // ---- supplier payments: maker-checker ---------------------------------------------------------------------------------------------------

    /**
     * @param  list<array{payable_id:int,amount:string}>  $allocations
     * @param  array{supplier_id:int,payment_date:string,method:string,bank_account_id?:?int,withheld_ait?:string,withheld_vat?:string,reference?:?string,note?:?string}  $d
     */
    public function createPayment(int $organizationId, array $d, array $allocations, User $by): SupplierPayment
    {
        if (! in_array($d['method'], ['bank', 'cash', 'wallet', 'cheque'], true)) {
            throw ValidationException::withMessages(['method' => 'Choose how the supplier is paid.']);
        }
        $bank = BankAccount::query()->where('organization_id', $organizationId)->where('active', true)->find($d['bank_account_id'] ?? 0);
        if ($bank === null) {
            throw ValidationException::withMessages(['bank_account_id' => 'Choose the bank, till or wallet the money leaves from.']);
        }
        if ($allocations === []) {
            throw ValidationException::withMessages(['allocations' => 'Choose at least one supplier invoice to pay.']);
        }
        $gross = '0.00';
        foreach ($allocations as $a) {
            $p = Payable::query()->where(['organization_id' => $organizationId, 'supplier_id' => $d['supplier_id']])->find($a['payable_id']);
            $amt = (string) $a['amount'];
            if ($p === null || ! is_numeric($amt) || bccomp($amt, '0', 2) <= 0 || bccomp($amt, $this->outstanding($p), 2) > 0) {
                throw ValidationException::withMessages(['allocations' => 'Each amount must be above zero and no more than what is still owed on that invoice (and the invoice must be this supplier\'s).']);
            }
            $gross = bcadd($gross, $amt, 2);
        }
        $ait = bcadd((string) ($d['withheld_ait'] ?? '0'), '0', 2);
        $vat = bcadd((string) ($d['withheld_vat'] ?? '0'), '0', 2);
        if (bccomp($ait, '0', 2) < 0 || bccomp($vat, '0', 2) < 0 || bccomp(bcadd($ait, $vat, 2), $gross, 2) > 0) {
            throw ValidationException::withMessages(['withheld_ait' => 'Tax withheld cannot be negative or more than the payment. (Amounts are entered by the accountant; this system does not decide the rate.)']);
        }

        return DB::transaction(function () use ($organizationId, $d, $allocations, $by, $bank, $gross, $ait, $vat) {
            $payment = SupplierPayment::create(['organization_id' => $organizationId, 'number' => $this->numbers->next('fin_supplier_payment', $organizationId, null, null, 'SP', '{PREFIX}-{YEAR}-{SEQ:6}', NumberSeriesService::PERIOD_YEAR, $by->id), 'supplier_id' => $d['supplier_id'],
                'payment_date' => $d['payment_date'], 'method' => $d['method'], 'bank_account_id' => $bank->id, 'gross_amount' => $gross, 'withheld_ait' => $ait, 'withheld_vat' => $vat, 'net_amount' => bcsub(bcsub($gross, $ait, 2), $vat, 2),
                'reference' => $d['reference'] ?? null, 'note' => $d['note'] ?? null, 'status' => 'draft', 'created_by' => $by->id]);
            foreach ($allocations as $a) {
                SupplierPaymentLine::create(['payment_id' => $payment->id, 'payable_id' => $a['payable_id'], 'amount' => bcadd((string) $a['amount'], '0', 2)]);
            }

            return $payment;
        });
    }

    public function approvePayment(SupplierPayment $payment, User $by): SupplierPayment
    {
        if ($payment->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Only a draft payment can be approved.']);
        }
        if ($this->policy->bool('payable.payment_second_approver', true) && $payment->created_by === $by->id) {
            throw ValidationException::withMessages(['approver' => 'A payment must be approved by someone other than the person who prepared it.']);
        }
        $payment->update(['status' => 'approved', 'approved_by' => $by->id, 'approved_at' => now()]);
        $this->audit->log('FIN_SUPPLIER_PAYMENT_APPROVED', SupplierPayment::class, $payment->id, null, ['number' => $payment->number, 'net' => (string) $payment->net_amount]);

        return $payment->refresh();
    }

    /** Pay an approved payment: Dr suppliers (gross), Cr bank (net), Cr tax withheld. The payables are cleared in the same step. */
    public function pay(SupplierPayment $payment, User $by): SupplierPayment
    {
        return DB::transaction(function () use ($payment, $by) {
            $locked = SupplierPayment::query()->lockForUpdate()->findOrFail($payment->id);
            if ($locked->status !== 'approved') {
                throw ValidationException::withMessages(['status' => 'Only an approved payment can be paid.']);
            }
            $org = $locked->organization_id;
            $bank = BankAccount::query()->findOrFail($locked->bank_account_id);
            $party = ['party_type' => 'supplier', 'party_id' => $locked->supplier_id];
            $lines = [['account' => $this->chart->accountFor($org, 'ap')->id, 'debit' => (string) $locked->gross_amount, 'credit' => '0'] + $party];
            bccomp((string) $locked->net_amount, '0', 2) > 0 && $lines[] = ['account' => $bank->account_id, 'debit' => '0', 'credit' => (string) $locked->net_amount, 'party_type' => 'bank', 'party_id' => $bank->id];
            bccomp((string) $locked->withheld_ait, '0', 2) > 0 && $lines[] = ['account' => $this->chart->accountFor($org, 'ait_payable')->id, 'debit' => '0', 'credit' => (string) $locked->withheld_ait, 'memo' => 'AIT/TDS withheld'];
            bccomp((string) $locked->withheld_vat, '0', 2) > 0 && $lines[] = ['account' => $this->chart->accountFor($org, 'vds_payable')->id, 'debit' => '0', 'credit' => (string) $locked->withheld_vat, 'memo' => 'VAT withheld at source'];
            $journal = $this->journals->postAuto($org, ['date' => $locked->payment_date->toDateString(), 'memo' => 'Supplier payment '.$locked->number, 'reference' => $locked->reference ?: $locked->number, 'source_type' => 'supplier_payment', 'source_id' => (string) $locked->id, 'event_key' => 'paid'], $lines, $by);
            foreach (SupplierPaymentLine::query()->where('payment_id', $locked->id)->get() as $line) {
                $p = Payable::query()->lockForUpdate()->findOrFail($line->payable_id);
                $settled = bcadd((string) $p->settled, (string) $line->amount, 2);
                $p->update(['settled' => $settled, 'status' => bccomp($settled, (string) $p->amount, 2) >= 0 ? 'settled' : 'open']);
            }
            $supplier = ProcurementSupplier::query()->find($locked->supplier_id);
            $this->tax->record($org, 'ait', $locked->payment_date->toDateString(), 'supplier_payment', $locked->id, (string) $locked->gross_amount, (string) $locked->withheld_ait, $journal, $locked->number, 'supplier', $locked->supplier_id, $supplier?->name, $supplier?->bin_number);
            $this->tax->record($org, 'vds', $locked->payment_date->toDateString(), 'supplier_payment', $locked->id, (string) $locked->gross_amount, (string) $locked->withheld_vat, $journal, $locked->number, 'supplier', $locked->supplier_id, $supplier?->name, $supplier?->bin_number);
            $locked->update(['status' => 'paid', 'paid_at' => now(), 'journal_id' => $journal->id]);
            $this->audit->log('FIN_SUPPLIER_PAYMENT_PAID', SupplierPayment::class, $locked->id, null, ['number' => $locked->number, 'journal' => $journal->number]);

            return $locked->refresh();
        });
    }

    public function cancelPayment(SupplierPayment $payment, string $reason): SupplierPayment
    {
        if (! in_array($payment->status, ['draft', 'approved'], true)) {
            throw ValidationException::withMessages(['status' => 'A paid payment is corrected by reversing its journal, not by cancelling.']);
        }
        $payment->update(['status' => 'cancelled', 'note' => mb_substr(trim(($payment->note ? $payment->note.' | ' : '').'Cancelled: '.$reason), 0, 250)]);

        return $payment->refresh();
    }

    /** Reverse a paid supplier payment: the journal is reversed and the invoices become payable again. */
    public function reversePayment(SupplierPayment $payment, User $by, string $reason): SupplierPayment
    {
        if ($payment->status !== 'paid' || ! $payment->journal_id) {
            throw ValidationException::withMessages(['status' => 'Only a paid payment can be reversed.']);
        }

        return DB::transaction(function () use ($payment, $by, $reason) {
            $this->journals->reverse(Journal::query()->findOrFail($payment->journal_id), $by, $reason);
            foreach (SupplierPaymentLine::query()->where('payment_id', $payment->id)->get() as $line) {
                $p = Payable::query()->lockForUpdate()->findOrFail($line->payable_id);
                $p->update(['settled' => bcsub((string) $p->settled, (string) $line->amount, 2), 'status' => 'open']);
            }
            $payment->update(['status' => 'cancelled', 'note' => mb_substr('Reversed: '.$reason, 0, 250)]);

            return $payment->refresh();
        });
    }

    /** @return array{buckets:list<string>,rows:list<array<string,mixed>>,total:string} */
    public function ageing(int $organizationId, ?string $asOf = null): array
    {
        $asOf = CarbonImmutable::parse($asOf ?? now())->startOfDay();
        $labels = ['Not yet due', '1-30 days', '31-60 days', '61-90 days', 'Over 90 days'];
        $rows = [];
        foreach (Payable::query()->where('organization_id', $organizationId)->where('status', 'open')->get() as $p) {
            $left = $this->outstanding($p);
            if (bccomp($left, '0', 2) <= 0) {
                continue;
            }
            $late = (int) ($p->due_date ?? $p->invoice_date)->startOfDay()->diffInDays($asOf, false);
            $idx = $late <= 0 ? 0 : ($late <= 30 ? 1 : ($late <= 60 ? 2 : ($late <= 90 ? 3 : 4)));
            $rows[$p->supplier_id] ??= ['supplier_id' => $p->supplier_id, 'name' => (string) (ProcurementSupplier::query()->whereKey($p->supplier_id)->value('name') ?? '#'.$p->supplier_id), 'buckets' => array_fill(0, 5, '0.00'), 'total' => '0.00'];
            $rows[$p->supplier_id]['buckets'][$idx] = bcadd($rows[$p->supplier_id]['buckets'][$idx], $left, 2);
            $rows[$p->supplier_id]['total'] = bcadd($rows[$p->supplier_id]['total'], $left, 2);
        }
        $rows = array_values($rows);
        usort($rows, fn ($a, $b) => (float) $b['total'] <=> (float) $a['total']);

        return ['buckets' => $labels, 'rows' => $rows, 'total' => array_reduce($rows, fn ($c, $r) => bcadd($c, $r['total'], 2), '0.00')];
    }
}
