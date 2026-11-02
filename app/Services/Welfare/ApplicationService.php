<?php

namespace App\Services\Welfare;

use App\Models\Billing\BillingAdjustment;
use App\Models\Billing\BillingInvoice;
use App\Models\User;
use App\Models\Welfare\WfApplication;
use App\Models\Welfare\WfFundEntry;
use App\Models\Welfare\WfGrant;
use App\Services\AuditLogger;
use App\Services\Billing\AdjustmentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Putting a grant on a bill. Welfare never edits an invoice: it asks Billing for a discount adjustment (reason: the grant), and Billing's own approver decides. The fund is charged, once, when that adjustment is approved
 * (the AdjustmentApproved event, with `welfare:check` as a safety net if the event was missed).
 */
class ApplicationService
{
    public function __construct(private readonly AdjustmentService $adjustments, private readonly GrantService $grants, private readonly AuditLogger $audit) {}

    public function apply(WfGrant $g, BillingInvoice $invoice, mixed $amount, User $by): WfApplication
    {
        return DB::transaction(function () use ($g, $invoice, $amount, $by) {
            $g = WfGrant::query()->with('fund')->lockForUpdate()->findOrFail($g->id);
            if ($g->status !== 'approved' || $g->valid_from->gt(today()) || $g->valid_to->lt(today())) {
                throw ValidationException::withMessages(['grant' => __('The grant is not in force.')]);
            }
            if ($invoice->organization_id !== $g->organization_id || $invoice->patient_id !== $g->patient_id) {
                throw ValidationException::withMessages(['invoice_no' => __('That bill is not this patient\'s.')]);
            }
            if (! in_array($invoice->status, ['finalized', 'partially_paid'], true) || bccomp((string) $invoice->due_amount, '0', 2) <= 0) {
                throw ValidationException::withMessages(['invoice_no' => __('Only a bill with something still to pay can be reduced.')]);
            }
            if (WfApplication::query()->where('invoice_id', $invoice->id)->where('status', 'requested')->exists()) {
                throw ValidationException::withMessages(['invoice_no' => __('A welfare discount for this bill is already waiting for Billing.')]);
            }
            $remaining = $this->grants->remaining($g);
            $share = bcdiv(bcmul((string) $invoice->grand_total, (string) $g->percent, 4), '100', 2);
            $max = $this->least([$remaining, $share, (string) $invoice->due_amount]);
            if ($amount === null || $amount === '') {
                $amount = $max;
            }
            if (! is_numeric($amount) || (float) $amount <= 0) {
                throw ValidationException::withMessages(['amount' => __('Enter an amount greater than zero.')]);
            }
            $amount = number_format((float) $amount, 2, '.', '');
            if (bccomp($amount, $max, 2) > 0) {
                throw ValidationException::withMessages(['amount' => __('This bill can take at most :m from this grant.', ['m' => $max])]);
            }
            $current = (string) $invoice->discount_amount;
            $adjustment = $this->adjustments->request($invoice, 'discount', $current, bcadd($current, $amount, 2), __('Welfare grant :n (:f)', ['n' => $g->number, 'f' => $g->fund->name]), $by);
            $app = WfApplication::create(['organization_id' => $g->organization_id, 'grant_id' => $g->id, 'patient_id' => $g->patient_id, 'invoice_id' => $invoice->id, 'adjustment_id' => $adjustment->id, 'amount' => $amount, 'status' => 'requested',
                'requested_by' => $by->id, 'requested_at' => now()]);
            $this->audit->log('CREATE', WfApplication::class, $app->id, null, ['grant' => $g->id, 'invoice' => $invoice->id, 'amount' => $amount]);

            return $app;
        });
    }

    /** Billing approved a discount: if it was a welfare one, charge the fund and the grant. Safe to call twice. */
    public function settle(BillingAdjustment $adjustment): ?WfApplication
    {
        return DB::transaction(function () use ($adjustment) {
            $app = WfApplication::query()->where('adjustment_id', $adjustment->id)->lockForUpdate()->first();
            if (! $app || $app->status !== 'requested' || $adjustment->status !== 'approved') {
                return $app;
            }
            $g = WfGrant::query()->lockForUpdate()->findOrFail($app->grant_id);
            WfFundEntry::create(['organization_id' => $app->organization_id, 'fund_id' => $g->fund_id, 'entry_type' => 'disbursement', 'amount' => bcmul((string) $app->amount, '-1', 2), 'grant_id' => $g->id, 'application_id' => $app->id,
                'reference' => $g->number, 'note' => __('Discount on bill approved by Billing'), 'occurred_at' => now(), 'created_by' => $adjustment->approved_by]);
            $spent = bcadd((string) $g->spent_amount, (string) $app->amount, 2);
            $g->update(['spent_amount' => $spent, 'status' => bccomp($spent, (string) $g->cap_amount, 2) >= 0 ? 'used' : $g->status]);
            $app->update(['status' => 'applied', 'settled_at' => now()]);
            $this->audit->log('UPDATE', WfApplication::class, $app->id, null, ['status' => 'applied']);

            return $app;
        });
    }

    /** Safety net: approved by Billing but not yet charged to the fund. @return int how many were put right */
    public function reconcile(int $organizationId): int
    {
        $n = 0;
        foreach (WfApplication::query()->where('organization_id', $organizationId)->where('status', 'requested')->whereHas('adjustment', fn ($q) => $q->where('status', 'approved'))->with('adjustment')->get() as $app) {
            $this->settle($app->adjustment) && $n++;
        }

        return $n;
    }

    /** @param list<string> $values */
    private function least(array $values): string
    {
        $min = $values[0];
        foreach ($values as $v) {
            if (bccomp($v, $min, 2) < 0) {
                $min = $v;
            }
        }

        return $min;
    }
}
