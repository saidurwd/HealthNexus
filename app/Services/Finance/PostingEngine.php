<?php

namespace App\Services\Finance;

use App\Models\Billing\BillingAdjustment;
use App\Models\Billing\BillingAdvanceAccount;
use App\Models\Billing\BillingAdvanceTransaction;
use App\Models\Billing\BillingInvoice;
use App\Models\Billing\BillingPayment;
use App\Models\Billing\BillingPaymentMethod;
use App\Models\Billing\BillingRefund;
use App\Models\Finance\BankAccount;
use App\Models\Finance\CostCenter;
use App\Models\Finance\FiscalYear;
use App\Models\Finance\Journal;
use App\Models\Finance\Posting;
use App\Models\Finance\Receivable;
use App\Models\Insurance\InsuranceClaim;
use App\Models\Insurance\InsuranceClaimEvent;
use App\Models\Insurance\InsuranceRemittanceLine;
use App\Models\Insurance\InsuranceWriteoff;
use App\Services\ModuleManager;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Turns what other modules did into journals. One handler per event; the account for each line comes from the account roles (data), the journal key is
 * (source type, source id, event) so a repeated event cannot post twice, and a handler that fails leaves a row in the failed list with its reason instead of breaking Billing.
 */
class PostingEngine
{
    /** @var array<string,string> event key => source type */
    public const EVENTS = [
        'invoice.finalized' => 'invoice', 'invoice.cancelled' => 'invoice', 'payment.completed' => 'payment', 'refund.processed' => 'refund', 'adjustment.approved' => 'adjustment',
        'advance.transaction' => 'advance', 'claim.accepted' => 'claim', 'remittance.line' => 'remittance_line', 'insurance.writeoff' => 'insurance_writeoff',
    ];

    /** @var array<string,?int> */
    private array $ccCache = [];

    public function __construct(
        private readonly ChartService $chart,
        private readonly JournalService $journals,
        private readonly ReceivableService $receivables,
        private readonly TaxRegister $tax,
        private readonly FinancePolicy $policy,
        private readonly ModuleManager $modules,
    ) {}

    /** On for the hospital AND set up (at least one fiscal year): until the accountant starts the books nothing is posted and nothing is recorded as failed. */
    public function enabled(int $organizationId): bool
    {
        return $this->modules->isEnabled('finance', $organizationId) && FiscalYear::query()->where('organization_id', $organizationId)->exists();
    }

    /** Post one source record. Returns its journal, or null when Finance is off, the record needs no entry, or the posting failed (see the failed list). */
    public function post(string $event, Model $source): ?Journal
    {
        $org = (int) $source->getAttribute('organization_id');
        if ($org === 0 || ! $this->enabled($org)) {
            return null;
        }
        $key = ['organization_id' => $org, 'source_type' => self::EVENTS[$event], 'source_id' => (string) $source->getKey(), 'event_key' => $event];
        $row = Posting::query()->where($key)->first();
        if ($row && in_array($row->status, ['posted', 'skipped'], true)) {
            return $row->journal_id ? Journal::query()->find($row->journal_id) : null;
        }
        try {
            $journal = DB::transaction(fn () => $this->handle($event, $source));
            Posting::query()->updateOrCreate($key, ['status' => $journal ? 'posted' : 'skipped', 'journal_id' => $journal?->id, 'error' => null, 'attempts' => ($row->attempts ?? 0) + 1, 'last_attempt_at' => now()]);

            return $journal;
        } catch (Throwable $e) {
            $message = $e instanceof ValidationException ? implode(' ', array_merge(...array_values($e->errors()))) : $e->getMessage();
            Posting::query()->updateOrCreate($key, ['status' => 'failed', 'journal_id' => null, 'error' => mb_substr($message, 0, 400), 'attempts' => ($row->attempts ?? 0) + 1, 'last_attempt_at' => now()]);

            return null;
        }
    }

    private function handle(string $event, Model $source): ?Journal
    {
        return match (true) {
            $event === 'invoice.finalized' && $source instanceof BillingInvoice => $this->invoice($source),
            $event === 'invoice.cancelled' && $source instanceof BillingInvoice => $this->invoiceCancelled($source),
            $event === 'payment.completed' && $source instanceof BillingPayment => $this->payment($source),
            $event === 'refund.processed' && $source instanceof BillingRefund => $this->refund($source),
            $event === 'adjustment.approved' && $source instanceof BillingAdjustment => $this->adjustment($source),
            $event === 'advance.transaction' && $source instanceof BillingAdvanceTransaction => $this->advance($source),
            $event === 'claim.accepted' && $source instanceof InsuranceClaim => $this->claim($source),
            $event === 'remittance.line' && $source instanceof InsuranceRemittanceLine => $this->remittanceLine($source),
            $event === 'insurance.writeoff' && $source instanceof InsuranceWriteoff => $this->insuranceWriteoff($source),
            default => throw new FinanceException("Cannot post $event for a ".class_basename($source).'.'),
        };
    }

    // ---- helpers --------------------------------------------------------------------------------------------------------------------------------

    private function auto(int $org, string $event, string $type, string|int $id, string $date, string $memo, array $lines, ?int $branchId = null, ?string $reference = null): Journal
    {
        $date = min($date, $this->day(now())); // the books are never dated in the future, whatever date a source carries

        return $this->journals->postAuto($org, ['date' => $date, 'memo' => $memo, 'reference' => $reference, 'branch_id' => $branchId, 'source_type' => $type, 'source_id' => (string) $id, 'event_key' => $event], $lines, null, $this->policy->bool('period.post_into_locked', false));
    }

    private function dr(Model|int|string $account, string $amount, array $extra = []): array
    {
        return ['account' => $account instanceof Model ? $account->getKey() : $account, 'debit' => $amount, 'credit' => '0'] + $extra;
    }

    private function cr(Model|int|string $account, string $amount, array $extra = []): array
    {
        return ['account' => $account instanceof Model ? $account->getKey() : $account, 'debit' => '0', 'credit' => $amount] + $extra;
    }

    private function arAccount(int $org, string $partyType): int
    {
        return $this->chart->accountFor($org, ['patient' => 'ar_patient', 'corporate' => 'ar_corporate', 'payer' => 'ar_insurer'][$partyType])->id;
    }

    /** Cost centre of a department (or branch), else the whole-hospital one. */
    private function costCenter(int $org, ?int $departmentId, ?int $branchId): ?int
    {
        $k = "$org:$departmentId:$branchId";

        return $this->ccCache[$k] ??= ($departmentId ? CostCenter::query()->where(['organization_id' => $org, 'department_id' => $departmentId, 'active' => true])->value('id') : null)
            ?? ($branchId ? CostCenter::query()->where(['organization_id' => $org, 'branch_id' => $branchId, 'active' => true])->whereNull('department_id')->value('id') : null)
            ?? CostCenter::query()->where(['organization_id' => $org, 'code' => 'HOSP'])->value('id');
    }

    private function methodAccount(int $org, ?BillingPaymentMethod $method): int
    {
        $code = $method?->code;
        if ($code) {
            $bank = BankAccount::query()->where(['organization_id' => $org, 'payment_method_code' => $code, 'active' => true])->first();
            if ($bank) {
                return $bank->account_id;
            }
            try {
                return $this->chart->accountFor($org, 'payment_method', $code)->id;
            } catch (FinanceException) {
                // fall through to the method's type
            }
        }
        $role = match ($method?->type) {
            'card' => 'gateway_clearing', 'mobile_financial_service' => 'wallet', 'bank_transfer' => 'bank_default', default => 'cash',
        };

        return $this->chart->accountFor($org, $role)->id;
    }

    // ---- Billing --------------------------------------------------------------------------------------------------------------------------------

    private function invoice(BillingInvoice $invoice): ?Journal
    {
        $org = $invoice->organization_id;
        if (bccomp((string) $invoice->grand_total, '0', 2) === 0 && bccomp((string) $invoice->subtotal, '0', 2) === 0) {
            return null;
        }
        $items = DB::table('billing_invoice_items as ii')->join('billing_items as bi', 'bi.id', '=', 'ii.billing_item_id')->leftJoin('billing_categories as bc', 'bc.id', '=', 'bi.category_id')->leftJoin('billing_charges as ch', 'ch.id', '=', 'ii.charge_id')
            ->where('ii.invoice_id', $invoice->id)->select('ii.gross_amount', 'ii.net_amount', 'ii.tax_amount', 'bc.code as category', 'ch.department_id')->get();
        $byRevenue = [];
        $taxBase = '0.00';
        foreach ($items as $it) {
            $account = $this->chart->accountFor($org, 'revenue', $it->category ?: null)->id;
            $cc = $this->costCenter($org, $it->department_id ? (int) $it->department_id : null, $invoice->branch_id);
            $k = "$account:$cc";
            $byRevenue[$k] = [$account, $cc, bcadd($byRevenue[$k][2] ?? '0.00', (string) $it->gross_amount, 2)];
            bccomp((string) $it->tax_amount, '0', 2) > 0 && $taxBase = bcadd($taxBase, (string) $it->net_amount, 2);
        }
        [$partyType, $partyId] = $this->receivables->invoiceParty($invoice);
        $ar = $this->arAccount($org, $partyType);
        $lines = [$this->dr($ar, (string) $invoice->grand_total, ['party_type' => $partyType, 'party_id' => $partyId])];
        $discount = (string) $invoice->discount_amount;
        bccomp($discount, '0', 2) > 0 && $lines[] = $this->dr($this->chart->accountFor($org, 'discount'), $discount);
        foreach ($byRevenue as [$account, $cc, $amount]) {
            $lines[] = $this->cr($account, $amount, ['cost_center' => $cc]);
        }
        $taxAmount = (string) $invoice->tax_amount;
        bccomp($taxAmount, '0', 2) > 0 && $lines[] = $this->cr($this->chart->accountFor($org, 'vat_output'), $taxAmount);
        $rounding = (string) $invoice->rounding_amount;
        if (bccomp($rounding, '0', 2) > 0) {
            $lines[] = $this->cr($this->chart->accountFor($org, 'rounding'), $rounding);
        } elseif (bccomp($rounding, '0', 2) < 0) {
            $lines[] = $this->dr($this->chart->accountFor($org, 'rounding'), bcmul($rounding, '-1', 2));
        }
        $date = $this->day($invoice->finalized_at ?? $invoice->created_at);
        $journal = $this->auto($org, 'invoice.finalized', 'invoice', $invoice->id, $date, 'Invoice '.$invoice->invoice_number, $lines, $invoice->branch_id, $invoice->invoice_number);
        $this->receivables->open($org, $invoice->branch_id, $partyType, $partyId, 'invoice', $invoice->id, $invoice->invoice_number, $date, $invoice->due_date ? Carbon::parse($invoice->due_date)->toDateString() : null, (string) $invoice->grand_total, $this->costCenter($org, null, $invoice->branch_id));
        $this->tax->record($org, 'vat_output', $date, 'invoice', $invoice->id, $taxBase, $taxAmount, $journal, $invoice->invoice_number, $partyType, $partyId, $this->receivables->party($partyType, $partyId));

        return $journal;
    }

    private function invoiceCancelled(BillingInvoice $invoice): ?Journal
    {
        $journal = Journal::query()->where(['organization_id' => $invoice->organization_id, 'source_type' => 'invoice', 'source_id' => (string) $invoice->id, 'event_key' => 'invoice.finalized'])->first();
        if ($journal === null) {
            return null; // it was never posted, so there is nothing to undo
        }
        $receivable = Receivable::query()->where(['organization_id' => $invoice->organization_id, 'source_type' => 'invoice', 'source_id' => $invoice->id])->first();
        $receivable?->update(['status' => 'cancelled']);

        return $journal->reversed_by_id ? Journal::query()->find($journal->reversed_by_id) : $this->journals->reverse($journal, auth()->user(), 'Invoice '.$invoice->invoice_number.' cancelled', $invoice->cancelled_at ?? now());
    }

    /** The day the claim was accepted (from its history), else the day it was sent, else today. */
    private function claimDate(InsuranceClaim $claim): string
    {
        $at = InsuranceClaimEvent::query()->where(['claim_id' => $claim->id, 'to_status' => 'accepted'])->min('occurred_at') ?? $claim->submitted_at ?? now();

        return $this->day(Carbon::parse($at, 'UTC'));
    }

    /** Accounting dates are the hospital's local dates (stored times are UTC). */
    private function day(CarbonInterface $d): string
    {
        return $d->copy()->setTimezone((string) config('finance.timezone', 'Asia/Dhaka'))->toDateString();
    }

    /** Which open item a payment settles: an insurer's payment settles the claim, a corporate settlement the corporate account, anything else the invoice's own party. @return array{0:string,1:int,2:string,3:string} party type, party id, source type, source id */
    private function paymentTarget(BillingPayment $payment, BillingInvoice $invoice, ?BillingPaymentMethod $method): array
    {
        if ($method?->code === 'INS') {
            $claim = InsuranceClaim::query()->where('invoice_id', $invoice->id)->where('payer_share', '>', 0)->orderByDesc('id')->first();
            if ($claim) {
                $this->ensureClaim($claim); // the payer's receivable exists before it is settled

                return ['payer', (int) $claim->payer_id, 'claim', (string) $claim->id];
            }
        }
        [$type, $id] = $this->receivables->invoiceParty($invoice);

        return [$type, $id, 'invoice', (string) $invoice->id];
    }

    /** The insurer's share of a claim must be in the books before anything settles it. */
    private function ensureClaim(InsuranceClaim $claim): void
    {
        $this->post('claim.accepted', $claim);
        if ($this->journalFor($claim, 'claim.accepted') === null) {
            throw new FinanceException('The insurer share of claim '.$claim->claim_number.' is not in the books yet.');
        }
    }

    /** A bill must be in the books before it is paid, adjusted or settled. */
    private function ensureInvoice(BillingInvoice $invoice): void
    {
        $this->post('invoice.finalized', $invoice);
        if ($this->journalFor($invoice, 'invoice.finalized') === null) {
            throw new FinanceException('Invoice '.$invoice->invoice_number.' is not in the books yet.');
        }
    }

    private function journalFor(Model $m, string $event): ?Journal
    {
        return Journal::query()->where(['organization_id' => $m->getAttribute('organization_id'), 'source_type' => self::EVENTS[$event], 'source_id' => (string) $m->getKey(), 'event_key' => $event])->first();
    }

    private function payment(BillingPayment $payment): ?Journal
    {
        $org = $payment->organization_id;
        $invoice = $payment->invoice_id ? BillingInvoice::query()->find($payment->invoice_id) : null;
        if ($invoice === null) {
            return null;
        }
        $this->ensureInvoice($invoice);
        $method = BillingPaymentMethod::query()->find($payment->payment_method_id);
        [$partyType, $partyId, $sourceType, $sourceId] = $this->paymentTarget($payment, $invoice, $method);
        $amount = (string) $payment->amount;
        $date = Carbon::parse($payment->payment_date)->toDateString();
        $journal = $this->auto($org, 'payment.completed', 'payment', $payment->id, $date, 'Payment '.$payment->payment_number, [
            $this->dr($this->methodAccount($org, $method), $amount),
            $this->cr($this->arAccount($org, $partyType), $amount, ['party_type' => $partyType, 'party_id' => $partyId]),
        ], $payment->branch_id, $payment->payment_number);
        $receivable = Receivable::query()->where(['organization_id' => $org, 'source_type' => $sourceType, 'source_id' => $sourceId])->first();
        $receivable && $this->receivables->apply($receivable, 'payment', $amount, 'payment', (string) $payment->id, $journal, $date);

        return $journal;
    }

    private function refund(BillingRefund $refund): ?Journal
    {
        $org = $refund->organization_id;
        $payment = BillingPayment::query()->find($refund->payment_id);
        $invoice = $refund->invoice_id ? BillingInvoice::query()->find($refund->invoice_id) : null;
        if ($payment === null || $invoice === null) {
            return null;
        }
        $this->post('payment.completed', $payment);
        if ($this->journalFor($payment, 'payment.completed') === null) {
            throw new FinanceException('Payment '.$payment->payment_number.' is not in the books yet.');
        }
        $method = BillingPaymentMethod::query()->find($payment->payment_method_id);
        [$partyType, $partyId, $sourceType, $sourceId] = $this->paymentTarget($payment, $invoice, $method);
        $amount = (string) $refund->amount;
        $date = $this->day($refund->processed_at ?? now());
        $journal = $this->auto($org, 'refund.processed', 'refund', $refund->id, $date, 'Refund '.$refund->refund_number, [
            $this->dr($this->arAccount($org, $partyType), $amount, ['party_type' => $partyType, 'party_id' => $partyId]),
            $this->cr($this->methodAccount($org, $method), $amount),
        ], $refund->branch_id, $refund->refund_number);
        $receivable = Receivable::query()->where(['organization_id' => $org, 'source_type' => $sourceType, 'source_id' => $sourceId])->first();
        $receivable && $this->receivables->apply($receivable, 'refund', bcmul($amount, '-1', 2), 'refund', (string) $refund->id, $journal, $date);

        return $journal;
    }

    private function adjustment(BillingAdjustment $adjustment): ?Journal
    {
        $org = $adjustment->organization_id;
        $invoice = $adjustment->invoice_id ? BillingInvoice::query()->find($adjustment->invoice_id) : null;
        if ($invoice === null) {
            return null;
        }
        $this->ensureInvoice($invoice);
        [$partyType, $partyId] = $this->receivables->invoiceParty($invoice);
        $receivable = Receivable::query()->where(['organization_id' => $org, 'source_type' => 'invoice', 'source_id' => $invoice->id])->first();
        $date = $this->day($adjustment->approved_at ?? now());
        $ar = $this->arAccount($org, $partyType);
        $party = ['party_type' => $partyType, 'party_id' => $partyId];
        $diff = (string) $adjustment->difference;

        if ($adjustment->type === 'write_off') {
            $left = $receivable ? $this->receivables->outstanding($receivable) : '0.00';
            if (bccomp($left, '0', 2) <= 0) {
                return null;
            }
            $journal = $this->auto($org, 'adjustment.approved', 'adjustment', $adjustment->id, $date, 'Write-off of invoice '.$invoice->invoice_number, [$this->dr($this->chart->accountFor($org, 'bad_debt'), $left), $this->cr($ar, $left, $party)], $invoice->branch_id);
            $this->receivables->apply($receivable, 'writeoff', $left, 'adjustment', (string) $adjustment->id, $journal, $date);

            return $journal;
        }
        if (bccomp($diff, '0', 2) === 0) {
            return null;
        }
        if (in_array($adjustment->type, ['discount', 'price', 'credit'], true)) {
            $abs = ltrim($diff, '-');
            $reduce = bccomp($diff, '0', 2) > 0; // a positive difference means the patient owes less
            $lines = $reduce
                ? [$this->dr($this->chart->accountFor($org, 'discount'), $abs), $this->cr($ar, $abs, $party)]
                : [$this->dr($ar, $abs, $party), $this->cr($this->chart->accountFor($org, 'discount'), $abs)];
        } else { // debit: the bill grows
            $abs = ltrim($diff, '-');
            $lines = [$this->dr($ar, $abs, $party), $this->cr($this->chart->accountFor($org, 'revenue'), $abs)];
        }
        $journal = $this->auto($org, 'adjustment.approved', 'adjustment', $adjustment->id, $date, ucfirst($adjustment->type).' adjustment on invoice '.$invoice->invoice_number, $lines, $invoice->branch_id);
        if ($receivable) {
            $in = in_array($adjustment->type, ['discount', 'price', 'credit'], true) ? $diff : bcmul($diff, '-1', 2);
            $this->receivables->apply($receivable, 'credit', $in, 'adjustment', (string) $adjustment->id, $journal, $date);
        }

        return $journal;
    }

    private function advance(BillingAdvanceTransaction $tx): ?Journal
    {
        $account = BillingAdvanceAccount::query()->find($tx->advance_account_id);
        if ($account === null) {
            return null;
        }
        $org = $account->organization_id;
        $amount = ltrim((string) $tx->amount, '-');
        $date = $this->day($tx->created_at);
        $party = ['party_type' => 'patient', 'party_id' => $account->patient_id];
        if ($tx->type === 'deposit') {
            $payment = $tx->payment_id ? BillingPayment::query()->find($tx->payment_id) : null;
            $method = $payment ? BillingPaymentMethod::query()->find($payment->payment_method_id) : null;

            return $this->auto($org, 'advance.transaction', 'advance', $tx->id, $date, 'Advance deposit', [$this->dr($this->methodAccount($org, $method), $amount), $this->cr($this->chart->accountFor($org, 'patient_advance'), $amount, $party)], $account->branch_id);
        }
        if ($tx->type === 'apply' && $tx->invoice_id) {
            $invoice = BillingInvoice::query()->find($tx->invoice_id);
            if ($invoice === null) {
                return null;
            }
            $this->ensureInvoice($invoice);
            [$partyType, $partyId] = $this->receivables->invoiceParty($invoice);
            $journal = $this->auto($org, 'advance.transaction', 'advance', $tx->id, $date, 'Advance applied to invoice '.$invoice->invoice_number, [
                $this->dr($this->chart->accountFor($org, 'patient_advance'), $amount, $party), $this->cr($this->arAccount($org, $partyType), $amount, ['party_type' => $partyType, 'party_id' => $partyId]),
            ], $account->branch_id);
            $receivable = Receivable::query()->where(['organization_id' => $org, 'source_type' => 'invoice', 'source_id' => $invoice->id])->first();
            $receivable && $this->receivables->apply($receivable, 'advance', $amount, 'advance', (string) $tx->id, $journal, $date);

            return $journal;
        }

        return null; // other advance movements (refunds of the deposit) are posted with the refund they belong to
    }

    // ---- Insurance --------------------------------------------------------------------------------------------------------------------------------

    /** An accepted claim moves what the insurer will pay from the patient's account to the insurer's. */
    private function claim(InsuranceClaim $claim): ?Journal
    {
        $org = $claim->organization_id;
        $share = (string) $claim->payer_share;
        $invoice = $claim->invoice_id ? BillingInvoice::query()->find($claim->invoice_id) : null;
        if ($invoice === null || bccomp($share, '0', 2) <= 0) {
            return null;
        }
        $existing = $this->journalFor($claim, 'claim.accepted');
        if ($existing) {
            return $existing;
        }
        $this->ensureInvoice($invoice);
        [$fromType, $fromId] = $this->receivables->invoiceParty($invoice);
        $date = $this->claimDate($claim);
        $journal = $this->auto($org, 'claim.accepted', 'claim', $claim->id, $date, 'Insurer share of claim '.$claim->claim_number, [
            $this->dr($this->arAccount($org, 'payer'), $share, ['party_type' => 'payer', 'party_id' => $claim->payer_id]),
            $this->cr($this->arAccount($org, $fromType), $share, ['party_type' => $fromType, 'party_id' => $fromId]),
        ], $claim->branch_id, $claim->claim_number);
        $this->receivables->open($org, $claim->branch_id, 'payer', (int) $claim->payer_id, 'claim', $claim->id, $claim->claim_number, $date, $claim->filing_deadline?->toDateString(), $share);
        $invoiceItem = Receivable::query()->where(['organization_id' => $org, 'source_type' => 'invoice', 'source_id' => $invoice->id])->first();
        $invoiceItem && $this->receivables->apply($invoiceItem, 'claim', $share, 'claim', (string) $claim->id, $journal, $date);

        return $journal;
    }

    private function remittanceLine(InsuranceRemittanceLine $line): ?Journal
    {
        $adjustment = (string) $line->adjustment_amount;
        if (bccomp($adjustment, '0', 2) <= 0) {
            return null; // the money part was posted with the Billing payment it created
        }
        $claim = InsuranceClaim::query()->find($line->claim_id);
        if ($claim === null) {
            return null;
        }
        $this->ensureClaim($claim);
        $org = $line->organization_id;
        $date = $this->day($line->created_at);
        $journal = $this->auto($org, 'remittance.line', 'remittance_line', $line->id, $date, 'Insurer disallowed on claim '.$claim->claim_number.($line->adjustment_reason ? ': '.$line->adjustment_reason : ''), [
            $this->dr($this->chart->accountFor($org, 'disallowance'), $adjustment), $this->cr($this->arAccount($org, 'payer'), $adjustment, ['party_type' => 'payer', 'party_id' => $claim->payer_id]),
        ], $claim->branch_id);
        $receivable = Receivable::query()->where(['organization_id' => $org, 'source_type' => 'claim', 'source_id' => $claim->id])->first();
        $receivable && $this->receivables->apply($receivable, 'remittance', $adjustment, 'remittance_line', (string) $line->id, $journal, $date);

        return $journal;
    }

    private function insuranceWriteoff(InsuranceWriteoff $writeoff): ?Journal
    {
        if ($writeoff->status !== 'approved') {
            return null;
        }
        $claim = InsuranceClaim::query()->find($writeoff->claim_id);
        if ($claim === null) {
            return null;
        }
        $this->ensureClaim($claim);
        $org = $writeoff->organization_id;
        $amount = (string) $writeoff->amount;
        $date = $this->day($writeoff->decided_at ?? now());
        $journal = $this->auto($org, 'insurance.writeoff', 'insurance_writeoff', $writeoff->id, $date, 'Write-off on claim '.$claim->claim_number, [
            $this->dr($this->chart->accountFor($org, 'disallowance'), $amount), $this->cr($this->arAccount($org, 'payer'), $amount, ['party_type' => 'payer', 'party_id' => $claim->payer_id]),
        ], $claim->branch_id);
        $receivable = Receivable::query()->where(['organization_id' => $org, 'source_type' => 'claim', 'source_id' => $claim->id])->first();
        $receivable && $this->receivables->apply($receivable, 'writeoff', $amount, 'insurance_writeoff', (string) $writeoff->id, $journal, $date);

        return $journal;
    }

    // ---- catch-up and retry ---------------------------------------------------------------------------------------------------------------------

    /**
     * Find Billing and Insurance records that never posted (the event was missed, the period was locked, an account was unmapped) and post them.
     *
     * @return array<string,int> posted count per event
     */
    public function catchUp(int $organizationId, ?int $days = null): array
    {
        if (! $this->enabled($organizationId)) {
            return [];
        }
        $since = now()->subDays($days ?? $this->policy->int('posting.catch_up_days', 45))->toDateString();
        $done = [];
        $each = function (string $event, $query, ?\Closure $keep = null) use (&$done, $organizationId) {
            $type = self::EVENTS[$event];
            $limit = $this->policy->int('posting.batch', 200);
            foreach ($query->orderBy('id')->pluck('id')->chunk(500) as $ids) {
                $donePosts = Posting::query()->where(['organization_id' => $organizationId, 'source_type' => $type, 'event_key' => $event])->whereIn('status', ['posted', 'skipped'])->whereIn('source_id', $ids->map(fn ($i) => (string) $i)->all())->pluck('source_id')->flip();
                $todo = $ids->reject(fn ($i) => $donePosts->has((string) $i))->take($limit)->all();
                if ($todo === []) {
                    continue;
                }
                $model = $query->getModel();
                foreach ($model->newQuery()->whereIn($model->getKeyName(), $todo)->orderBy($model->getKeyName())->get() as $m) {
                    if (($keep === null || $keep($m)) && $this->post($event, $m)) {
                        $done[$event] = ($done[$event] ?? 0) + 1;
                    }
                }
            }
        };
        $each('invoice.finalized', BillingInvoice::query()->where('organization_id', $organizationId)->whereNotNull('finalized_at')->where('finalized_at', '>=', $since)->whereNotIn('status', ['draft', 'cancelled']));
        $each('invoice.cancelled', BillingInvoice::query()->where('organization_id', $organizationId)->where('status', 'cancelled')->where('cancelled_at', '>=', $since), fn (BillingInvoice $i) => Journal::query()->where(['organization_id' => $organizationId, 'source_type' => 'invoice', 'source_id' => (string) $i->id, 'event_key' => 'invoice.finalized'])->exists());
        $each('payment.completed', BillingPayment::query()->where('organization_id', $organizationId)->whereIn('status', ['completed', 'refunded'])->where('payment_date', '>=', $since)->whereNotNull('invoice_id'));
        $each('refund.processed', BillingRefund::query()->where('organization_id', $organizationId)->where('status', 'processed')->where('processed_at', '>=', $since));
        $each('adjustment.approved', BillingAdjustment::query()->where('organization_id', $organizationId)->where('status', 'approved')->where('approved_at', '>=', $since));
        $each('advance.transaction', BillingAdvanceTransaction::query()->whereIn('type', ['deposit', 'apply'])->where('created_at', '>=', $since)->whereIn('advance_account_id', BillingAdvanceAccount::query()->where('organization_id', $organizationId)->select('id')));
        $each('claim.accepted', InsuranceClaim::query()->where('organization_id', $organizationId)->whereIn('status', ['accepted', 'partially_paid', 'paid', 'appealed', 'denied', 'written_off', 'closed'])->where('payer_share', '>', 0)->whereNotNull('invoice_id'));
        $each('remittance.line', InsuranceRemittanceLine::query()->where('organization_id', $organizationId)->where('adjustment_amount', '>', 0)->where('created_at', '>=', $since));
        $each('insurance.writeoff', InsuranceWriteoff::query()->where('organization_id', $organizationId)->where('status', 'approved'));

        return $done;
    }

    /** Try the failed postings again (after the accountant fixed a mapping or reopened a period). @return array{retried:int,posted:int} */
    public function retryFailed(int $organizationId, ?int $limit = null): array
    {
        $models = ['invoice' => BillingInvoice::class, 'payment' => BillingPayment::class, 'refund' => BillingRefund::class, 'adjustment' => BillingAdjustment::class, 'advance' => BillingAdvanceTransaction::class, 'claim' => InsuranceClaim::class,
            'remittance_line' => InsuranceRemittanceLine::class, 'insurance_writeoff' => InsuranceWriteoff::class];
        $retried = $posted = 0;
        foreach (Posting::query()->where(['organization_id' => $organizationId, 'status' => 'failed'])->where('attempts', '<', $this->policy->int('posting.retry_limit', 20))->orderBy('id')->limit($limit ?? $this->policy->int('posting.batch', 200))->get() as $row) {
            $source = isset($models[$row->source_type]) ? $models[$row->source_type]::query()->find($row->source_id) : null;
            if ($source === null) {
                continue;
            }
            $retried++;
            $this->post($row->event_key, $source) && $posted++;
        }

        return ['retried' => $retried, 'posted' => $posted];
    }

    public static function sourceLabel(string $type): string
    {
        return Str::headline($type);
    }
}
