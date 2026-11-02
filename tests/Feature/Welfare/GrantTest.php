<?php

namespace Tests\Feature\Welfare;

use App\Models\Welfare\WfAssessment;
use App\Models\Welfare\WfGrant;
use App\Services\Welfare\AssessmentService;
use App\Services\Welfare\FundService;
use App\Services\Welfare\GrantService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class GrantTest extends WelfareTestCase
{
    public function test_a_grant_needs_an_approved_assessment_in_force_that_found_a_need(): void
    {
        $svc = app(GrantService::class);
        $fund = $this->fundWith('9000');
        $draft = app(AssessmentService::class)->start($this->patient(), ['monthly_income' => 1000, 'household_size' => 2], $this->actor([]));
        $none = $this->approvedAssessment(null, ['monthly_income' => 90000, 'household_size' => 2]);

        foreach ([$draft, $none] as $a) {
            try {
                $svc->request($a, $fund, '1000', null, null, $this->actor([]));
                $this->fail('refused');
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }
        $g = $svc->request($this->approvedAssessment(), $fund, '1000', null, 'Surgery', $this->actor([]));
        $this->assertSame('requested', $g->status);
        $this->assertSame(100, $g->percent, 'the assessment\'s suggestion is the default');
        $this->assertStringStartsWith('WG-', $g->number);
    }

    public function test_a_fund_can_limit_what_one_patient_gets(): void
    {
        $fund = $this->fundWith('90000', ['per_grant_cap' => 10000]);
        $this->expectException(ValidationException::class);
        app(GrantService::class)->request($this->approvedAssessment(), $fund, '10000.01', null, null, $this->actor([]));
    }

    public function test_a_second_person_approves_and_the_money_is_set_aside(): void
    {
        $svc = app(GrantService::class);
        $funds = app(FundService::class);
        $fund = $this->fundWith('8000');
        $requester = $this->actor([]);
        $g = $svc->request($this->approvedAssessment(), $fund, '3000', null, null, $requester);

        try {
            $svc->approve($g, $requester);
            $this->fail('the requester cannot approve');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('second person', implode(' ', $e->errors()['grant']));
        }
        $this->assertSame('8000.00', $funds->available($fund), 'nothing is set aside before approval');
        $svc->approve($g->refresh(), $this->actor([]), 'Agreed');
        $this->assertSame('5000.00', $funds->available($fund));
        $this->assertSame('8000.00', $funds->balance($fund), 'setting aside is not spending');
    }

    public function test_a_grant_cannot_be_approved_for_more_than_the_fund_has_free(): void
    {
        $svc = app(GrantService::class);
        $fund = $this->fundWith('5000');
        $this->approvedGrant($fund, null, '4000.00');
        $second = $svc->request($this->approvedAssessment(), $fund, '2000', null, null, $this->actor([]));

        try {
            $svc->approve($second, $this->actor([]));
            $this->fail('only 1,000 is free');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('1000.00', implode(' ', $e->errors()['grant']));
        }
        $this->assertSame('requested', $second->fresh()->status);
    }

    public function test_one_patient_has_one_open_grant_at_a_time(): void
    {
        $fund = $this->fundWith('9000');
        $patient = $this->patient();
        $this->approvedGrant($fund, $patient, '1000.00');
        $this->expectException(ValidationException::class);
        app(GrantService::class)->request(WfAssessment::query()->where('patient_id', $patient->id)->first(), $fund, '500', null, null, $this->actor([]));
    }

    public function test_a_turn_down_needs_a_reason_and_sets_nothing_aside(): void
    {
        $svc = app(GrantService::class);
        $fund = $this->fundWith('5000');
        $g = $svc->request($this->approvedAssessment(), $fund, '2000', null, null, $this->actor([]));
        try {
            $svc->reject($g, $this->actor([]), 'no');
            $this->fail('a reason is needed');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }
        $svc->reject($g->refresh(), $this->actor([]), 'The family has other cover.');
        $this->assertSame('rejected', $g->fresh()->status);
        $this->assertSame('5000.00', app(FundService::class)->available($fund));
    }

    public function test_a_grant_past_its_date_lapses_and_gives_the_money_back_to_the_fund(): void
    {
        $fund = $this->fundWith('5000');
        $g = $this->approvedGrant($fund, null, '3000.00');
        $this->assertSame('2000.00', app(FundService::class)->available($fund));
        Carbon::setTestNow(now()->addDays(91));

        $this->assertSame(1, app(GrantService::class)->expireDue($this->organization->id));
        $this->assertSame('expired', $g->fresh()->status);
        $this->assertSame('5000.00', app(FundService::class)->available($fund));
        Carbon::setTestNow();
    }

    public function test_a_grant_is_never_valid_beyond_its_assessment(): void
    {
        $fund = $this->fundWith('5000');
        $g = $this->approvedGrant($fund);
        $this->assertTrue($g->valid_to->lte($g->assessment->valid_until));
    }

    public function test_a_stopped_grant_releases_its_money(): void
    {
        $fund = $this->fundWith('5000');
        $g = $this->approvedGrant($fund, null, '3000.00');
        app(GrantService::class)->cancel($g, $this->actor([]), 'The patient went elsewhere');
        $this->assertSame('cancelled', WfGrant::find($g->id)->status);
        $this->assertSame('5000.00', app(FundService::class)->available($fund));
    }
}
