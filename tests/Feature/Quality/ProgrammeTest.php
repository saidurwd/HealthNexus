<?php

namespace Tests\Feature\Quality;

use App\Models\Ed\EdVisit;
use App\Models\Quality\QaIndicator;
use App\Models\Quality\QaIndicatorValue;
use App\Models\Quality\QaMortalityReview;
use App\Services\Analytics\Warehouse;
use App\Services\Quality\ClinicalAuditService;
use App\Services\Quality\IndicatorService;
use App\Services\Quality\InfectionService;
use App\Services\Quality\MortalityService;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class ProgrammeTest extends QualityTestCase
{
    private function value(string $code, string $period = '2026-08-01'): ?QaIndicatorValue
    {
        return QaIndicatorValue::query()->where('indicator_id', QaIndicator::query()->where('code', $code)->value('id'))->where('period', $period)->first();
    }

    private function census(string $month = '202608', int $occupied = 10): void
    {
        $wh = app(Warehouse::class);
        for ($d = 1; $d <= 30; $d++) {
            $wh->table('fact_bed_census')->insert(['date_key' => (int) ($month.sprintf('%02d', $d)), 'organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'beds_total' => 20, 'beds_occupied' => $occupied]);
        }
    }

    public function test_rates_are_numerators_over_bed_days_and_a_month_without_a_denominator_has_no_value(): void
    {
        $this->census();
        $report = $this->actor(['quality.incident.report']);
        foreach (['fall', 'fall', 'pressure_injury'] as $c) {
            $this->incident($report, ['category' => $c, 'occurred_at' => '2026-08-10 10:00', 'harm' => 'minor']);
        }
        $this->incident($report, ['category' => 'fall', 'kind' => 'near_miss', 'occurred_at' => '2026-08-11 10:00']); // a near miss is not a fall
        app(IndicatorService::class)->install();
        app(IndicatorService::class)->computeMonth($this->organization->id, CarbonImmutable::parse('2026-08-15'));
        $falls = $this->value('falls_per_1000');
        $this->assertSame([2.0, 300.0], [(float) $falls->numerator, (float) $falls->denominator]);
        $this->assertEqualsWithDelta(6.6667, (float) $falls->value, 0.001);
        $this->assertEqualsWithDelta(3.3333, (float) $this->value('pressure_injury_per_1000')->value, 0.001);
        $this->assertEqualsWithDelta(13.3333, (float) $this->value('incident_reports_per_1000')->value, 0.001);
        $this->assertEqualsWithDelta(25.0, (float) $this->value('near_miss_share')->value, 0.001);
        app(IndicatorService::class)->computeMonth($this->organization->id, CarbonImmutable::parse('2026-07-15'));
        $this->assertNull($this->value('falls_per_1000', '2026-07-01')->value, 'no bed-days: no value, never zero');
    }

    public function test_readmission_looks_for_a_return_within_30_days(): void
    {
        $wh = app(Warehouse::class);
        $row = fn (int $id, int $pk, int $in, int $out, string $status = 'closed') => $wh->table('fact_admissions')->insert(['admission_id' => $id, 'organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'date_key' => $in, 'discharge_date_key' => $out, 'patient_key' => $pk, 'status' => $status, 'is_discharged' => true]);
        $row(1, 100, 20260801, 20260805);
        $row(2, 100, 20260815, 20260820); // 1 came back 10 days after discharge
        $row(3, 200, 20260802, 20260806);
        $row(4, 200, 20261020, 20261022); // far too late
        app(IndicatorService::class)->install();
        app(IndicatorService::class)->computeMonth($this->organization->id, CarbonImmutable::parse('2026-08-15'));
        $v = $this->value('readmission_30d');
        $this->assertSame([1.0, 3.0], [(float) $v->numerator, (float) $v->denominator]);
        $this->assertEqualsWithDelta(33.3333, (float) $v->value, 0.001);
    }

    public function test_hand_hygiene_and_infection_rates_feed_the_indicators(): void
    {
        $this->census();
        $ic = $this->actor(['quality.infection.manage']);
        app(InfectionService::class)->auditHandHygiene($this->organization->id, ['staff_role' => 'nurse', 'moments_observed' => 50, 'moments_compliant' => 40, 'audited_on' => '2026-08-05'], $ic);
        $case = app(InfectionService::class)->reportCase($this->organization->id, ['patient_id' => $this->patient()->id, 'hai_type' => 'cauti', 'onset_on' => '2026-08-09'], $ic);
        $case->update(['hospital_day' => 5]);
        app(InfectionService::class)->decide($case, $ic, true);
        app(IndicatorService::class)->install();
        app(IndicatorService::class)->computeMonth($this->organization->id, CarbonImmutable::parse('2026-08-15'));
        $this->assertEqualsWithDelta(80.0, (float) $this->value('hand_hygiene')->value, 0.001);
        $this->assertEqualsWithDelta(3.3333, (float) $this->value('hai_per_1000')->value, 0.001);
    }

    public function test_on_target_follows_the_direction(): void
    {
        $svc = app(IndicatorService::class);
        $svc->install();
        $falls = QaIndicator::query()->where('code', 'falls_per_1000')->first(); // down, target 3
        $this->assertTrue($svc->onTarget($falls, 2.0));
        $this->assertFalse($svc->onTarget($falls, 4.0));
        $hh = QaIndicator::query()->where('code', 'hand_hygiene')->first(); // up, target 80
        $this->assertTrue($svc->onTarget($hh, 85.0));
        $this->assertFalse($svc->onTarget($hh, 60.0));
        $this->assertNull($svc->onTarget(QaIndicator::query()->where('code', 'inpatient_mortality')->first(), 5.0), 'no target set');
        $this->assertNull($svc->onTarget($falls, null));
    }

    public function test_every_death_gets_one_review_that_a_reviewer_classifies(): void
    {
        $visit = EdVisit::factory()->create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'status' => 'dispositioned', 'disposition_type' => 'death', 'dispositioned_at' => '2026-09-05 12:00:00']);
        $visit->patient()->update(['status' => 'deceased', 'deceased_at' => '2026-09-05 11:50:00']);
        $svc = app(MortalityService::class);
        $this->assertSame(1, $svc->sweep($this->organization->id));
        $this->assertSame(0, $svc->sweep($this->organization->id), 'once');
        $review = QaMortalityReview::query()->first();
        $this->assertMatchesRegularExpression('/^MR-2026-\d{5}$/', $review->number);
        $this->assertSame('2026-09-05 11:50:00', $review->died_at->toDateTimeString());
        $r = $this->actor(['quality.mortality.review']);
        try {
            $svc->review($review, $r, 'made_up', 'Findings that are long enough to be accepted.', null);
            $this->fail('unknown classification');
        } catch (ValidationException) {
        }
        $done = $svc->review($review, $r, 'potentially_preventable', 'Delay of 40 minutes before the senior review; no sign of a monitoring failure.', 'Call the senior earlier for chest pain');
        $this->assertSame(['reviewed', $r->id], [$done->status, $done->reviewer_id]);
        $this->expectException(ValidationException::class);
        $svc->review($done->fresh(), $r, 'expected', 'Trying to review it a second time.', null);
    }

    public function test_deaths_reviewed_in_time_counts_reviews_inside_the_window(): void
    {
        foreach ([['2026-08-03', '2026-08-10'], ['2026-08-05', null], ['2026-08-07', '2026-10-30']] as $n => [$died, $reviewed]) {
            QaMortalityReview::create(['organization_id' => $this->organization->id, 'number' => 'MR-X-'.$n, 'patient_id' => $this->patient()->id, 'source_type' => 'ed_visit', 'source_id' => 900 + $n, 'died_at' => $died.' 08:00:00',
                'status' => $reviewed ? 'reviewed' : 'pending', 'reviewed_at' => $reviewed ? $reviewed.' 08:00:00' : null, 'classification' => $reviewed ? 'expected' : null]);
        }
        app(IndicatorService::class)->install();
        app(IndicatorService::class)->computeMonth($this->organization->id, CarbonImmutable::parse('2026-08-15'));
        $this->assertSame([1.0, 3.0], [(float) $this->value('mortality_review_on_time')->numerator, (float) $this->value('mortality_review_on_time')->denominator]);
    }

    public function test_a_clinical_audit_counts_criteria_and_lists_the_gaps(): void
    {
        $a = $this->actor(['quality.audit.manage']);
        $svc = app(ClinicalAuditService::class);
        $audit = $svc->create($this->organization->id, ['title' => 'Surgical safety checklist use', 'standard' => 'All three phases signed', 'sample_size' => 20], $a);
        $this->assertMatchesRegularExpression('/^AUD-2026-\d{4}$/', $audit->number);
        try {
            $svc->complete($audit, $a, null);
            $this->fail('nothing recorded');
        } catch (ValidationException) {
        }
        $svc->record($audit, [['criterion' => 'Sign-in done', 'met' => 20, 'not_met' => 0], ['criterion' => 'Time-out done', 'met' => 17, 'not_met' => 3], ['criterion' => 'Sign-out done', 'met' => 10, 'not_met' => 10]]);
        $done = $svc->complete($audit->fresh(), $a, 'Sign-out is the weak step');
        $this->assertSame(78.33, (float) $done->compliance);
        $gaps = $svc->gaps($done->fresh('findings'));
        $this->assertSame(['Time-out done', 'Sign-out done'], array_column($gaps, 'criterion'));
        $this->expectException(ValidationException::class);
        $svc->record($done, [['criterion' => 'x', 'met' => 1, 'not_met' => 0]]);
    }
}
