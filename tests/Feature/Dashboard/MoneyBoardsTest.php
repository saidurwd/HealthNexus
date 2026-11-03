<?php

namespace Tests\Feature\Dashboard;

use App\Services\Billing\BillingOverview;
use App\Services\Insurance\InsuranceOverview;
use App\Support\Dashboard\DashboardPeriod;
use Carbon\CarbonImmutable;

/** The Billing, Insurance and Finance boards on the dashboard standard. Now is Thursday 12 Nov 2026, 10:00 in Dhaka (04:00 UTC). */
class MoneyBoardsTest extends FrontDoorTestCase
{
    private function period(string $key = '7d'): DashboardPeriod
    {
        return DashboardPeriod::make($key, now: CarbonImmutable::now());
    }

    private function invoice(string $date, float $total, string $status = 'finalized', float $paid = 0, array $extra = []): int
    {
        return $this->filled('billing_invoices', $extra + ['invoice_date' => $date, 'grand_total' => $total, 'paid_amount' => $paid, 'due_amount' => $total - $paid, 'status' => $status]);
    }

    public function test_each_money_board_states_itself_and_refuses_a_person_without_the_right(): void
    {
        $this->assertStringContainsString('billed and collected', $this->assertDashboardStatesItself('/billing', $this->actor(['billing.dashboard.view'])));
        $this->assertStringContainsString('claims being paid', $this->assertDashboardStatesItself('/insurance', $this->actor(['insurance.dashboard.view'])));
        $this->assertStringContainsString('books in order', $this->assertDashboardStatesItself('/finance', $this->actor(['finance.dashboard.view'])));
        $this->assertDashboardRefuses('/billing', $this->actor(['billing.invoice.view']));
        $this->assertDashboardRefuses('/insurance', $this->actor(['insurance.claim.view']));
        $this->assertDashboardRefuses('/finance', $this->actor(['finance.journal.view']));
    }

    public function test_billing_counts_what_was_billed_collected_and_is_still_owed_by_the_day(): void
    {
        $this->invoice('2026-11-12', 1000, 'partially_paid', 400);
        $this->invoice('2026-11-10', 500, 'paid', 500);
        $this->invoice('2026-11-11', 300, 'draft');                                    // a draft is not billed
        $this->invoice('2026-10-01', 200, 'finalized', 0, ['corporate_id' => 1]);      // older, still owed
        $method = $this->filled('billing_payment_methods', ['type' => 'cash']);
        $this->filled('billing_payments', ['payment_method_id' => $method, 'amount' => 400, 'payment_date' => '2026-11-12', 'status' => 'completed']);
        $this->filled('billing_payments', ['payment_method_id' => $method, 'amount' => 999, 'payment_date' => '2026-11-12', 'status' => 'cancelled']);

        $s = app(BillingOverview::class)->summary($this->organization->id, $this->branch->id, $this->period());

        $this->assertSame([1500.0, 400.0, 26.7, 2], [$s['current']['billed'], $s['current']['collected'], $s['current']['collection_rate'], $s['current']['invoices']], 'drafts and cancelled payments are not counted');
        $this->assertSame([800.0, 200.0, 2], [$s['owed'], $s['corporate_owed'], $s['owing_bills']], '600 + 200 are still due');
        $this->assertSame(800.0, array_sum(array_column($s['aging'], 'owed')), 'the age bands add up to what is owed');
        $this->assertSame(400.0, $s['by_method'][0]['taka']);
        $this->assertCount(7, $s['per_day']);
    }

    public function test_billing_does_not_count_another_hospital_and_has_no_names(): void
    {
        $other = $this->otherHospital();
        $this->invoice('2026-11-12', 5000, 'finalized', 0, ['organization_id' => $other->id]);

        $s = app(BillingOverview::class)->summary($this->organization->id, $this->branch->id, $this->period());

        $this->assertSame([0.0, 0.0], [$s['current']['billed'], $s['owed']]);
        $this->assertNull($s['current']['collection_rate'], 'nothing billed: no rate is claimed');
    }

    public function test_insurance_measures_claims_sent_in_the_period_and_their_outcome(): void
    {
        $claim = fn (string $status, string $sent, array $x = []) => $this->filled('insurance_claims', $x + ['status' => $status, 'submitted_at' => $sent, 'payer_share' => 1000, 'paid_amount' => $status === 'paid' ? 1000 : 0, 'updated_at' => '2026-11-12 03:00:00']);
        $claim('paid', '2026-11-09 04:00:00');          // paid after 3 days
        $claim('denied', '2026-11-10 04:00:00');
        $claim('submitted', '2026-11-11 04:00:00');     // not decided yet
        $claim('paid', '2026-10-01 04:00:00');          // sent before the period

        $s = app(InsuranceOverview::class)->summary($this->organization->id, $this->branch->id, $this->period());

        $this->assertSame([3, 3000.0, 1000.0], [$s['current']['sent'], $s['current']['sent_value'], $s['current']['paid']]);
        $this->assertSame([50.0, 2], [$s['current']['denial_rate'], $s['current']['decided']], 'one denied of two decided');
        $this->assertSame(3, $s['current']['days_to_payment']['median']);
    }

    public function test_insurance_says_nothing_about_a_rate_when_no_claim_was_decided(): void
    {
        $s = app(InsuranceOverview::class)->summary($this->organization->id, $this->branch->id, $this->period());

        $this->assertNull($s['current']['denial_rate']);
        $this->assertNull($s['current']['days_to_payment']);
    }
}
