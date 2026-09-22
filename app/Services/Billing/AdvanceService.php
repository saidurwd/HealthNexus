<?php

namespace App\Services\Billing;

use App\Models\Billing\BillingAdvanceAccount;
use App\Models\Billing\BillingAdvanceTransaction;
use App\Models\Billing\BillingInvoice;
use App\Models\Billing\BillingPayment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Ledger-based patient advances. billing_advance_accounts.balance is only ever a cached snapshot
 * written atomically alongside each ledger row — balance() recomputes the authoritative figure
 * from billing_advance_transactions rather than trusting that snapshot for financial decisions.
 */
class AdvanceService
{
    public function getOrCreateAccount(int $companyId, int $branchId, Patient $patient): BillingAdvanceAccount
    {
        return BillingAdvanceAccount::query()->firstOrCreate(
            ['company_id' => $companyId, 'patient_id' => $patient->id],
            ['branch_id' => $branchId, 'currency' => 'BDT', 'balance' => '0.00', 'status' => 'active'],
        );
    }

    public function deposit(BillingAdvanceAccount $account, string $amount, User $user, ?BillingPayment $payment = null): BillingAdvanceTransaction
    {
        return DB::transaction(function () use ($account, $amount, $user, $payment) {
            if (bccomp($amount, '0', 2) <= 0) {
                throw ValidationException::withMessages(['amount' => 'Deposit amount must be greater than zero.']);
            }

            $account = BillingAdvanceAccount::query()->whereKey($account->id)->lockForUpdate()->first();
            $newBalance = bcadd($this->balance($account), $amount, 2);

            $transaction = BillingAdvanceTransaction::create([
                'advance_account_id' => $account->id,
                'payment_id' => $payment?->id,
                'invoice_id' => null,
                'type' => 'deposit',
                'amount' => $amount,
                'balance_after' => $newBalance,
                'reference_type' => $payment ? BillingPayment::class : null,
                'reference_id' => $payment?->id,
                'idempotency_key' => $payment ? "payment:{$payment->id}" : (string) Str::uuid(),
                'performed_by' => $user->id,
            ]);

            $account->update(['balance' => $newBalance]);

            return $transaction;
        });
    }

    public function applyToInvoice(BillingAdvanceAccount $account, BillingInvoice $invoice, string $amount, User $user): BillingAdvanceTransaction
    {
        return DB::transaction(function () use ($account, $invoice, $amount, $user) {
            if (bccomp($amount, '0', 2) <= 0) {
                throw ValidationException::withMessages(['amount' => 'Amount must be greater than zero.']);
            }

            $account = BillingAdvanceAccount::query()->whereKey($account->id)->lockForUpdate()->first();
            $currentBalance = $this->balance($account);

            if (bccomp($amount, $currentBalance, 2) === 1) {
                throw ValidationException::withMessages(['amount' => 'Amount exceeds the available advance balance.']);
            }

            if (bccomp($amount, $invoice->remainingDue(), 2) === 1) {
                throw ValidationException::withMessages(['amount' => 'Amount exceeds the invoice due amount.']);
            }

            $newBalance = bcsub($currentBalance, $amount, 2);

            $transaction = BillingAdvanceTransaction::create([
                'advance_account_id' => $account->id,
                'payment_id' => null,
                'invoice_id' => $invoice->id,
                'type' => 'apply',
                'amount' => bcmul($amount, '-1', 2),
                'balance_after' => $newBalance,
                'reference_type' => BillingInvoice::class,
                'reference_id' => $invoice->id,
                'idempotency_key' => "invoice-apply:{$invoice->id}:".Str::uuid(),
                'performed_by' => $user->id,
            ]);

            $account->update(['balance' => $newBalance]);

            $newPaid = bcadd((string) $invoice->paid_amount, $amount, 2);
            $newDue = bcsub((string) $invoice->grand_total, $newPaid, 2);

            $invoice->update([
                'paid_amount' => $newPaid,
                'due_amount' => $newDue,
                'status' => bccomp($newDue, '0', 2) <= 0 ? 'paid' : 'partially_paid',
            ]);

            return $transaction;
        });
    }

    public function balance(BillingAdvanceAccount $account): string
    {
        return $account->transactions()
            ->pluck('amount')
            ->reduce(fn (string $carry, $amount) => bcadd($carry, (string) $amount, 2), '0.00');
    }
}
