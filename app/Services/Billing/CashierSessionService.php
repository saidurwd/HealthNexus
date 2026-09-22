<?php

namespace App\Services\Billing;

use App\Events\Billing\CashierSessionClosed;
use App\Events\Billing\CashierSessionOpened;
use App\Models\Billing\BillingCashierSession;
use App\Models\Billing\BillingRefund;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Reconciliation is cash-specific (a cashier physically counts currency in a drawer, not card
 * slips), so expected collections/refunds only sum payments made through a 'cash' payment
 * method. One open session per user is an application-level invariant (no DB unique index for
 * it), enforced here.
 */
class CashierSessionService
{
    public function open(User $user, int $companyId, int $branchId, string $openingBalance, ?Department $counter = null): BillingCashierSession
    {
        return DB::transaction(function () use ($user, $companyId, $branchId, $openingBalance, $counter) {
            $hasOpenSession = BillingCashierSession::query()
                ->where('user_id', $user->id)
                ->where('status', 'open')
                ->exists();

            if ($hasOpenSession) {
                throw ValidationException::withMessages(['session' => 'You already have an open cashier session. Close it before opening a new one.']);
            }

            $session = BillingCashierSession::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'counter_id' => $counter?->id,
                'user_id' => $user->id,
                'status' => 'open',
                'currency' => 'BDT',
                'opening_balance' => $openingBalance,
                'expected_collections' => '0.00',
                'expected_refunds' => '0.00',
                'expected_closing' => $openingBalance,
                'actual_closing' => '0.00',
                'variance' => '0.00',
                'opened_at' => now(),
            ]);

            event(new CashierSessionOpened($session));

            return $session;
        });
    }

    public function close(BillingCashierSession $session, string $actualClosing, ?string $notes, User $user): BillingCashierSession
    {
        return DB::transaction(function () use ($session, $actualClosing, $notes, $user) {
            if (! $session->isOpen()) {
                throw ValidationException::withMessages(['session' => 'This cashier session is already closed.']);
            }

            $reconciliation = $this->reconcile($session);
            $variance = bcsub($actualClosing, $reconciliation['expected_closing'], 2);

            $session->update([
                'expected_collections' => $reconciliation['expected_collections'],
                'expected_refunds' => $reconciliation['expected_refunds'],
                'expected_closing' => $reconciliation['expected_closing'],
                'actual_closing' => $actualClosing,
                'variance' => $variance,
                'status' => 'closed',
                'closed_at' => now(),
                'closed_by' => $user->id,
                'closing_notes' => $notes,
            ]);

            $session = $session->refresh();

            event(new CashierSessionClosed($session));

            return $session;
        });
    }

    /**
     * @return array{expected_collections:string,expected_refunds:string,expected_closing:string}
     */
    public function reconcile(BillingCashierSession $session): array
    {
        $cashPayments = $session->payments()
            ->where('status', 'completed')
            ->whereHas('method', fn ($q) => $q->where('type', 'cash'))
            ->get(['id', 'amount']);

        $cashCollections = $cashPayments->pluck('amount')
            ->reduce(fn (string $carry, $amount) => bcadd($carry, (string) $amount, 2), '0.00');

        $cashRefunds = BillingRefund::query()
            ->where('status', 'processed')
            ->whereIn('payment_id', $cashPayments->pluck('id'))
            ->pluck('amount')
            ->reduce(fn (string $carry, $amount) => bcadd($carry, (string) $amount, 2), '0.00');

        $expectedClosing = bcsub(bcadd((string) $session->opening_balance, $cashCollections, 2), $cashRefunds, 2);

        return [
            'expected_collections' => $cashCollections,
            'expected_refunds' => $cashRefunds,
            'expected_closing' => $expectedClosing,
        ];
    }
}
