<?php

namespace Tests\Feature\Welfare;

use App\Models\Organization;
use App\Models\Welfare\WfFundEntry;
use App\Services\Welfare\FundService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FundTest extends WelfareTestCase
{
    public function test_a_fund_needs_a_name_a_known_kind_and_a_name_not_already_used(): void
    {
        $svc = app(FundService::class);
        foreach ([['name' => 'ab', 'kind' => 'zakat'], ['name' => 'Good fund', 'kind' => 'lottery']] as $bad) {
            try {
                $svc->createFund($this->organization->id, $bad);
                $this->fail('refused');
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }
        $svc->createFund($this->organization->id, ['name' => 'Zakat fund', 'kind' => 'zakat']);
        $this->expectException(ValidationException::class);
        $svc->createFund($this->organization->id, ['name' => 'Zakat fund', 'kind' => 'zakat']);
    }

    public function test_a_donation_gets_a_numbered_receipt_and_the_balance_is_the_sum_of_the_ledger(): void
    {
        $svc = app(FundService::class);
        $fund = $this->fund();
        $donor = $svc->donor($this->organization->id, ['name' => 'Mr Karim', 'kind' => 'individual']);
        $a = $svc->recordDonation($fund, '5000', $donor, $this->actor([]), 'Cheque 123');
        $b = $svc->recordDonation($fund, '2500.50', null, $this->actor([]));

        $this->assertNotSame($a->receipt_no, $b->receipt_no);
        $this->assertStringStartsWith('WR-', $a->receipt_no);
        $this->assertSame('7500.50', $svc->balance($fund));
        $this->assertSame('7500.50', $svc->available($fund));
    }

    public function test_an_amount_must_be_greater_than_zero_and_a_closed_fund_takes_nothing(): void
    {
        $svc = app(FundService::class);
        $fund = $this->fund();
        foreach (['0', '-5', 'abc'] as $bad) {
            try {
                $svc->recordDonation($fund, $bad, null, $this->actor([]));
                $this->fail('refused');
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }
        $svc->close($fund, $this->actor([]), 'No longer used');
        $this->expectException(ValidationException::class);
        $svc->recordDonation($fund->refresh(), '100', null, $this->actor([]));
    }

    public function test_the_ledger_cannot_be_changed_or_deleted_by_anyone(): void
    {
        $entry = app(FundService::class)->recordDonation($this->fund(), '1000', null, $this->actor([]));
        foreach ([fn () => DB::table('wf_fund_entries')->where('id', $entry->id)->update(['amount' => 1]), fn () => DB::table('wf_fund_entries')->where('id', $entry->id)->delete()] as $try) {
            try {
                $try();
                $this->fail('the database must refuse');
            } catch (\Throwable $e) {
                $this->assertStringContainsString('never changed', $e->getMessage());
            }
        }
        $this->assertSame('1000.00', (string) $entry->fresh()->amount);
    }

    public function test_a_mistake_is_put_right_with_a_reversal_that_says_why_and_only_once(): void
    {
        $svc = app(FundService::class);
        $fund = $this->fundWith('3000');
        $entry = WfFundEntry::query()->where('fund_id', $fund->id)->first();

        try {
            $svc->reverse($entry, $this->actor([]), 'no');
            $this->fail('a reason is needed');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }
        $svc->reverse($entry, $this->actor([]), 'Entered on the wrong fund');
        $this->assertSame('0.00', $svc->balance($fund));
        $this->expectException(ValidationException::class);
        $svc->reverse($entry, $this->actor([]), 'Again');
    }

    public function test_a_reversal_cannot_take_away_money_that_approved_grants_may_still_use(): void
    {
        $svc = app(FundService::class);
        $fund = $this->fundWith('5000');
        $this->approvedGrant($fund, null, '4000.00');
        $this->assertSame('1000.00', $svc->available($fund));

        $this->expectException(ValidationException::class);
        $svc->reverse(WfFundEntry::query()->where('fund_id', $fund->id)->first(), $this->actor([]), 'Wrong fund');
    }

    public function test_a_fund_with_open_grants_cannot_be_closed(): void
    {
        $fund = $this->fundWith('5000');
        $this->approvedGrant($fund);
        $this->expectException(ValidationException::class);
        app(FundService::class)->close($fund, $this->actor([]), 'Closing it');
    }

    public function test_another_hospitals_donor_cannot_be_used(): void
    {
        $other = Organization::factory()->create();
        $donor = app(FundService::class)->donor($other->id, ['name' => 'Someone Else']);
        $this->expectException(ValidationException::class);
        app(FundService::class)->recordDonation($this->fund(), '100', $donor, $this->actor([]));
    }
}
