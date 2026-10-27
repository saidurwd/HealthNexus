<?php

namespace App\Services\Insurance;

use App\Models\Billing\BillingCorporate;
use App\Models\Billing\BillingCorporateContract;
use App\Models\Billing\BillingInvoice;
use App\Models\Billing\BillingPaymentMethod;
use App\Models\Insurance\CorporateBatch;
use App\Models\User;
use App\Services\Billing\PaymentService;
use App\Services\Numbering\NumberSeriesService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Corporate credit billing: the credit limit, and monthly statements (batches) of a client's invoices that the client pays as one. */
class CorporateCreditService
{
    public function __construct(private readonly NumberSeriesService $numbers, private readonly PaymentService $payments) {}

    /** What the corporate client owes today and what it may still owe. @return array{limit: ?string, owed: string, available: ?string} */
    public function position(BillingCorporate $corporate): array
    {
        $contract = BillingCorporateContract::query()->where('corporate_id', $corporate->id)->where('status', 'active')->whereNotNull('credit_limit')->where('credit_limit', '>', 0)->latest('id')->first();
        $limit = $contract ? (string) $contract->credit_limit : null;
        if ($limit === null) {
            $own = (string) $corporate->getAttribute('credit_limit');
            $limit = $own !== '' && bccomp($own, '0', 2) > 0 ? $own : null;
        }
        $owed = (string) BillingInvoice::query()->where('corporate_id', $corporate->id)->whereIn('status', ['finalized', 'partially_paid'])->sum('due_amount');
        $owed = number_format((float) $owed, 2, '.', '');

        return ['limit' => $limit, 'owed' => $owed, 'available' => $limit !== null ? bcsub((string) $limit, $owed, 2) : null];
    }

    /** Refuses a new corporate bill that would take the client over its credit limit. */
    public function assertCanBill(BillingCorporate $corporate, string $amount): void
    {
        $p = $this->position($corporate);
        if ($p['available'] !== null && bccomp($amount, $p['available'], 2) > 0) {
            throw ValidationException::withMessages(['corporate' => "{$corporate->name} has {$p['available']} of credit left (limit {$p['limit']}, owes {$p['owed']}); this bill is $amount."]);
        }
    }

    /** Gathers the client's finalised, unpaid, unbatched invoices of a period into a statement. */
    public function createBatch(BillingCorporate $corporate, string $from, string $to, User $by): CorporateBatch
    {
        return DB::transaction(function () use ($corporate, $from, $to, $by) {
            $invoices = BillingInvoice::query()->where('corporate_id', $corporate->id)->whereIn('status', ['finalized', 'partially_paid'])->whereDate('invoice_date', '>=', $from)->whereDate('invoice_date', '<=', $to)
                ->whereNotIn('id', DB::table('corporate_batch_invoices')->pluck('invoice_id'))->orderBy('invoice_date')->orderBy('id')->lockForUpdate()->get();
            if ($invoices->isEmpty()) {
                throw ValidationException::withMessages(['period' => 'No unbilled invoices for this client in that period.']);
            }
            $batch = CorporateBatch::create([
                'organization_id' => $corporate->organization_id, 'corporate_id' => $corporate->id, 'period_from' => $from, 'period_to' => $to, 'invoice_count' => $invoices->count(), 'created_by' => $by->id,
                'batch_number' => $this->numbers->next('corporate.batch', $corporate->organization_id, null, prefix: 'CRP', format: '{PREFIX}-{YEAR}-{SEQ:6}', userId: $by->id),
                'total_amount' => $invoices->reduce(fn ($c, $i) => bcadd($c, (string) $i->due_amount, 2), '0.00'),
            ]);
            foreach ($invoices as $invoice) {
                DB::table('corporate_batch_invoices')->insert(['batch_id' => $batch->id, 'invoice_id' => $invoice->id, 'amount' => $invoice->due_amount, 'created_at' => now(), 'updated_at' => now()]);
            }

            return $batch->refresh();
        });
    }

    public function issue(CorporateBatch $batch, User $by): CorporateBatch
    {
        if ($batch->status !== 'open') {
            throw ValidationException::withMessages(['batch' => 'Only an open statement can be issued.']);
        }
        $terms = (int) (BillingCorporate::whereKey($batch->corporate_id)->value('payment_terms_days') ?: 30);
        $batch->update(['status' => 'issued', 'issued_at' => now(), 'due_on' => now()->addDays($terms)->toDateString()]);

        return $batch->refresh();
    }

    /** The client pays against the statement; the money is shared over its invoices, oldest first, each a real payment. */
    public function receivePayment(CorporateBatch $batch, string $amount, User $by, ?string $reference = null): CorporateBatch
    {
        return DB::transaction(function () use ($batch, $amount, $by, $reference) {
            $locked = CorporateBatch::query()->lockForUpdate()->findOrFail($batch->id);
            if ($locked->status !== 'issued') {
                throw ValidationException::withMessages(['batch' => 'Issue the statement before taking payment against it.']);
            }
            $owed = bcsub((string) $locked->total_amount, (string) $locked->paid_amount, 2);
            if (bccomp($amount, '0', 2) <= 0 || bccomp($amount, $owed, 2) > 0) {
                throw ValidationException::withMessages(['amount' => "Enter an amount above zero and no more than the $owed still owed."]);
            }
            $method = BillingPaymentMethod::firstOrCreate(['organization_id' => $locked->organization_id, 'branch_id' => null, 'code' => 'CORP'], ['name' => 'Corporate account', 'type' => 'bank_transfer', 'is_active' => true]);
            $left = $amount;
            foreach (DB::table('corporate_batch_invoices')->where('batch_id', $locked->id)->orderBy('id')->pluck('invoice_id') as $invoiceId) {
                if (bccomp($left, '0', 2) <= 0) {
                    break;
                }
                $invoice = BillingInvoice::findOrFail($invoiceId);
                $part = bccomp($left, $invoice->remainingDue(), 2) < 0 ? $left : $invoice->remainingDue();
                if (bccomp($part, '0', 2) > 0 && in_array($invoice->status, ['finalized', 'partially_paid'], true)) {
                    $this->payments->collect($invoice, ['payment_method_id' => $method->id, 'amount' => $part, 'transaction_reference' => $reference ?: $locked->batch_number], $by);
                    $left = bcsub($left, $part, 2);
                }
            }
            $paid = bcadd((string) $locked->paid_amount, bcsub($amount, $left, 2), 2);
            $locked->update(['paid_amount' => $paid, 'status' => bccomp($paid, (string) $locked->total_amount, 2) >= 0 ? 'settled' : 'issued']);

            return $locked->refresh();
        });
    }
}
