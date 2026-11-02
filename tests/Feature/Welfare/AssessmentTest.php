<?php

namespace Tests\Feature\Welfare;

use App\Services\Welfare\AssessmentService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class AssessmentTest extends WelfareTestCase
{
    public function test_income_per_person_decides_the_suggested_category_at_every_boundary(): void
    {
        $svc = app(AssessmentService::class);
        // examples from config/welfare.php: free under 3,000 a head, subsidised under 6,000
        foreach ([[11996, 4, 'free', 100], [12000, 4, 'subsidised', 50], [23996, 4, 'subsidised', 50], [24000, 4, 'none', 0], [0, 1, 'free', 100]] as [$income, $size, $category, $percent]) {
            $r = $svc->work((float) $income, $size, []);
            $this->assertSame([$category, $percent], [$r[2], $r[3]], "$income for $size");
        }
    }

    public function test_circumstances_can_move_a_person_up_one_category_and_never_two(): void
    {
        $svc = app(AssessmentService::class);
        $this->assertSame('subsidised', $svc->work(40000, 4, ['no_earner'])[2], 'two points move none → subsidised');
        $this->assertSame('free', $svc->work(15000, 4, ['no_earner', 'widow_headed'])[2], 'subsidised → free');
        $this->assertSame('free', $svc->work(500, 1, ['no_earner', 'widow_headed', 'orphan'])[2]);
        $this->assertSame('none', $svc->work(40000, 4, ['orphan'])[2], 'one point is not enough');
    }

    public function test_an_assessment_needs_income_a_household_and_known_circumstances_only(): void
    {
        $svc = app(AssessmentService::class);
        $patient = $this->patient();
        foreach ([['monthly_income' => '', 'household_size' => 3], ['monthly_income' => -1, 'household_size' => 3], ['monthly_income' => 500, 'household_size' => 0]] as $bad) {
            try {
                $svc->start($patient, $bad, $this->actor([]));
                $this->fail('refused');
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }
        $a = $svc->start($patient, ['monthly_income' => 6000, 'household_size' => 3, 'factors' => ['no_earner', 'made_up']], $this->actor([]));
        $this->assertSame(['no_earner'], $a->factors);
        $this->assertSame('2000.00', (string) $a->per_head);
        $this->assertStringStartsWith('WA-', $a->number);
    }

    public function test_it_cannot_go_to_the_committee_without_saying_what_was_checked(): void
    {
        $svc = app(AssessmentService::class);
        $a = $svc->start($this->patient(), ['monthly_income' => 1000, 'household_size' => 2], $this->actor([]));
        $this->expectException(ValidationException::class);
        $svc->submit($a, $this->actor([]));
    }

    public function test_a_second_person_decides_and_a_turn_down_needs_a_reason(): void
    {
        $svc = app(AssessmentService::class);
        $assessor = $this->actor([]);
        $a = $svc->start($this->patient(), ['monthly_income' => 1000, 'household_size' => 2, 'evidence_note' => 'Letter from the ward councillor seen.'], $assessor);
        $svc->submit($a, $assessor);

        try {
            $svc->decide($a->refresh(), $assessor, true);
            $this->fail('the assessor cannot decide');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('second person', implode(' ', $e->errors()['assessment']));
        }
        try {
            $svc->decide($a->refresh(), $this->actor([]), false, 'no');
            $this->fail('a reason is needed');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }
        $done = $svc->decide($a->refresh(), $this->actor([]), true, 'Agreed');
        $this->assertSame('approved', $done->status);
        $this->assertTrue($done->valid_until->isSameDay(today()->addDays(180)));
    }

    public function test_a_patient_with_an_assessment_waiting_or_in_force_cannot_be_assessed_again(): void
    {
        $patient = $this->patient();
        $this->approvedAssessment($patient);
        $this->expectException(ValidationException::class);
        app(AssessmentService::class)->start($patient, ['monthly_income' => 1000, 'household_size' => 2], $this->actor([]));
    }

    public function test_an_approved_assessment_lapses_on_its_date(): void
    {
        $a = $this->approvedAssessment();
        Carbon::setTestNow(now()->addDays(181));
        $this->assertSame(1, app(AssessmentService::class)->expireDue($this->organization->id));
        $this->assertSame('expired', $a->fresh()->status);
        Carbon::setTestNow();
    }
}
