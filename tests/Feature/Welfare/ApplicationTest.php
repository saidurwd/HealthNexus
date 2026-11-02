<?php

namespace Tests\Feature\Welfare;

use App\Models\Billing\BillingAdjustment;
use App\Models\Welfare\WfApplication;
use App\Models\Welfare\WfFundEntry;
use App\Services\Billing\AdjustmentService;
use App\Services\Welfare\ApplicationService;
use App\Services\Welfare\FundService;
use App\Services\Welfare\GrantService;
use App\Services\Welfare\WelfareCheck;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** The waiver reaches the bill only as a Billing discount adjustment; the fund is charged when Billing approves it. */
class ApplicationTest extends WelfareTestCase
{
    public function test_applying_asks_billing_and_changes_neither_the_bill_nor_the_fund(): void
    {
        $patient = $this->patient();
        $fund = $this->fundWith('10000');
        $g = $this->approvedGrant($fund, $patient, '5000.00', 50);
        $invoice = $this->bill($patient, '3000.00');

        $app = app(ApplicationService::class)->apply($g, $invoice, null, $this->actor([]));

        $this->assertSame('requested', $app->status);
        $this->assertSame('1500.00', (string) $app->amount, '50% of 3,000');
        $adj = BillingAdjustment::find($app->adjustment_id);
        $this->assertSame(['discount', 'requested', '1500.00'], [$adj->type, $adj->status, (string) $adj->difference]);
        $this->assertStringContainsString($g->number, $adj->reason);
        $this->assertSame('3000.00', (string) $invoice->fresh()->due_amount, 'the bill is untouched until Billing approves');
        $this->assertSame('10000.00', app(FundService::class)->balance($fund));
        $this->assertSame('3500.00', app(GrantService::class)->remaining($g->fresh()), 'what is waiting for Billing is already counted against the grant');
    }

    public function test_when_billing_approves_the_bill_falls_and_the_fund_and_grant_are_charged_once(): void
    {
        $patient = $this->patient();
        $fund = $this->fundWith('10000');
        $g = $this->approvedGrant($fund, $patient, '5000.00');
        $invoice = $this->bill($patient, '3000.00');
        $app = app(ApplicationService::class)->apply($g, $invoice, null, $this->actor([]));

        app(AdjustmentService::class)->approve(BillingAdjustment::find($app->adjustment_id), $this->actor([]));

        $this->assertSame('applied', $app->fresh()->status);
        $this->assertSame('0.00', (string) $invoice->fresh()->due_amount, 'a 100% grant clears a 3,000 bill');
        $this->assertSame('7000.00', app(FundService::class)->balance($fund));
        $this->assertSame('3000.00', (string) $g->fresh()->spent_amount);
        $this->assertSame('approved', $g->fresh()->status, '2,000 of the 5,000 is still there');
        $this->assertSame(1, WfFundEntry::query()->where('application_id', $app->id)->count());

        app(ApplicationService::class)->settle(BillingAdjustment::find($app->adjustment_id));
        $this->assertSame('7000.00', app(FundService::class)->balance($fund), 'settling twice charges once');
    }

    public function test_a_grant_that_is_all_given_is_marked_used(): void
    {
        $patient = $this->patient();
        $fund = $this->fundWith('10000');
        $g = $this->approvedGrant($fund, $patient, '3000.00');
        $app = app(ApplicationService::class)->apply($g, $this->bill($patient, '3000.00'), null, $this->actor([]));
        app(AdjustmentService::class)->approve(BillingAdjustment::find($app->adjustment_id), $this->actor([]));

        $this->assertSame('used', $g->fresh()->status);
        $this->assertSame('0.00', app(FundService::class)->committed($fund), 'nothing stays set aside');
    }

    public function test_the_amount_is_limited_by_the_grant_the_share_of_the_bill_and_what_is_still_to_pay(): void
    {
        $patient = $this->patient();
        $fund = $this->fundWith('10000');
        $g = $this->approvedGrant($fund, $patient, '1000.00', 100);
        $invoice = $this->bill($patient, '3000.00');
        $svc = app(ApplicationService::class);

        try {
            $svc->apply($g, $invoice, '1000.01', $this->actor([]));
            $this->fail('more than the grant has');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('1000.00', implode(' ', $e->errors()['amount']));
        }
        $this->assertSame('1000.00', (string) $svc->apply($g, $invoice, null, $this->actor([]))->amount, 'the most allowed is the default');
    }

    public function test_it_cannot_reduce_another_patients_bill_or_one_already_waiting(): void
    {
        $patient = $this->patient();
        $fund = $this->fundWith('10000');
        $g = $this->approvedGrant($fund, $patient, '5000.00');
        $svc = app(ApplicationService::class);

        try {
            $svc->apply($g, $this->bill($this->patient()), null, $this->actor([]));
            $this->fail('another patient');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('invoice_no', $e->errors());
        }
        $invoice = $this->bill($patient, '2000.00');
        $svc->apply($g, $invoice, '500', $this->actor([]));
        $this->expectException(ValidationException::class);
        $svc->apply($g->fresh(), $invoice, '500', $this->actor([]));
    }

    public function test_only_a_grant_in_force_can_be_applied(): void
    {
        $patient = $this->patient();
        $fund = $this->fundWith('10000');
        $g = $this->approvedGrant($fund, $patient, '5000.00');
        app(GrantService::class)->cancel($g, $this->actor([]), 'Stopped for a test');
        $this->expectException(ValidationException::class);
        app(ApplicationService::class)->apply($g->fresh(), $this->bill($patient), null, $this->actor([]));
    }

    public function test_a_grant_with_a_discount_still_waiting_for_billing_cannot_be_stopped(): void
    {
        $patient = $this->patient();
        $fund = $this->fundWith('10000');
        $g = $this->approvedGrant($fund, $patient, '5000.00');
        app(ApplicationService::class)->apply($g, $this->bill($patient), '500', $this->actor([]));
        $this->expectException(ValidationException::class);
        app(GrantService::class)->cancel($g->fresh(), $this->actor([]), 'Changed our minds');
    }

    public function test_if_the_event_is_missed_the_daily_check_charges_the_fund(): void
    {
        $patient = $this->patient();
        $fund = $this->fundWith('10000');
        $g = $this->approvedGrant($fund, $patient, '5000.00');
        $app = app(ApplicationService::class)->apply($g, $this->bill($patient, '1000.00'), null, $this->actor([]));
        // Billing approved it but the listener never ran
        DB::table('billing_adjustments')->where('id', $app->adjustment_id)->update(['status' => 'approved', 'approved_by' => $this->actor([])->id, 'approved_at' => now()]);

        $out = app(WelfareCheck::class)->run($this->organization->id);

        $this->assertSame(1, $out['settled']);
        $this->assertSame('applied', WfApplication::find($app->id)->status);
        $this->assertSame('9000.00', app(FundService::class)->balance($fund));
    }
}
