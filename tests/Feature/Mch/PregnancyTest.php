<?php

namespace Tests\Feature\Mch;

use App\Models\Mch\MchAncVisit;
use App\Models\Mch\MchNewborn;
use App\Models\Mch\MchPregnancy;
use App\Models\Patient;
use App\Models\Vital\VrBirthNotification;
use App\Services\Mch\PregnancyService;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Vital\VitalTestCase;

class PregnancyTest extends VitalTestCase
{
    private function svc(): PregnancyService
    {
        return app(PregnancyService::class);
    }

    private function book(?Patient $w = null, array $o = []): MchPregnancy
    {
        return $this->svc()->book($w ?? $this->mother(), $o + ['lmp' => now()->subWeeks(20)->toDateString(), 'gravida' => 2, 'para' => 1], $this->actor());
    }

    private function refused(callable $fn, string $field): void
    {
        try {
            $fn();
            $this->fail("expected [$field] to be refused");
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($field, $e->errors());
        }
    }

    public function test_the_expected_date_is_280_days_after_the_last_period_unless_given(): void
    {
        $lmp = now()->subWeeks(10)->toDateString();
        $p = $this->book(null, ['lmp' => $lmp]);
        $given = $this->book(null, ['lmp' => null, 'edd' => '2027-03-01']);

        $this->assertSame(CarbonImmutable::parse($lmp)->addDays(280)->toDateString(), $p->edd->toDateString());
        $this->assertSame('2027-03-01', $given->edd->toDateString());
        $this->assertMatchesRegularExpression('/^PR-\d{4}-00001$/', $p->number);
    }

    public function test_booking_is_refused_for_a_man_a_second_live_pregnancy_a_bad_date_or_impossible_counts(): void
    {
        $w = $this->mother();
        $this->book($w);
        $this->refused(fn () => $this->book($w), 'patient_id');
        $this->refused(fn () => $this->svc()->book($this->patient(['sex' => 'M']), ['lmp' => now()->subWeeks(5)->toDateString()], $this->actor()), 'patient_id');
        $this->refused(fn () => $this->book(null, ['lmp' => now()->addDay()->toDateString()]), 'lmp');
        $this->refused(fn () => $this->book(null, ['lmp' => now()->subDays(400)->toDateString()]), 'lmp');
        $this->refused(fn () => $this->book(null, ['lmp' => null]), 'edd');
        $this->refused(fn () => $this->book(null, ['gravida' => 2, 'para' => 2]), 'para');
    }

    public function test_only_known_risk_factors_are_kept(): void
    {
        $p = $this->book(null, ['risk_factors' => ['hypertension', 'made_up']]);

        $this->assertSame(['hypertension'], $p->risk_factors);
    }

    public function test_weeks_are_counted_back_from_the_expected_date(): void
    {
        $p = $this->book(null, ['lmp' => now()->subWeeks(20)->toDateString()]);

        $this->assertSame(20, $this->svc()->weeksOn($p));
    }

    public function test_readings_raise_the_right_alerts(): void
    {
        $a = fn (array $d) => $this->svc()->alertsFor($d);

        $this->assertSame([], $a(['bp_systolic' => 118, 'bp_diastolic' => 76, 'hemoglobin' => 12.1, 'fetal_heart_rate' => 140]));
        $this->assertSame(['bp_high'], $a(['bp_systolic' => 142, 'bp_diastolic' => 88]));
        $this->assertSame(['bp_severe'], $a(['bp_systolic' => 120, 'bp_diastolic' => 112]));
        $this->assertSame(['anaemia'], $a(['hemoglobin' => 10.2]));
        $this->assertSame(['anaemia_severe'], $a(['hemoglobin' => 6.5]));
        $this->assertSame(['fetal_heart_rate'], $a(['fetal_heart_rate' => 100]));
        $this->assertSame(['bp_high', 'pre_eclampsia'], $a(['bp_systolic' => 150, 'bp_diastolic' => 95, 'urine_protein' => 'plus2']));
        $this->assertSame(['danger_sign'], $a(['danger_signs' => ['bleeding']]));
    }

    public function test_visits_are_numbered_keep_their_alerts_and_unknown_signs_are_dropped(): void
    {
        $p = $this->book();
        $first = $this->svc()->recordVisit($p, ['bp_systolic' => 120, 'bp_diastolic' => 78], $this->actor());
        $second = $this->svc()->recordVisit($p->refresh(), ['bp_systolic' => 150, 'bp_diastolic' => 100, 'danger_signs' => ['severe_headache', 'nonsense']], $this->actor());

        $this->assertSame([1, 2], [$first->visit_no, $second->visit_no]);
        $this->assertSame(['bp_high', 'danger_sign'], $second->alerts);
        $this->assertSame(['severe_headache'], $second->danger_signs);
        $this->assertSame(20, $first->weeks);
    }

    public function test_a_visit_cannot_be_in_the_future_or_on_a_closed_pregnancy(): void
    {
        $p = $this->book();
        $this->refused(fn () => $this->svc()->recordVisit($p, ['visited_at' => now()->addDay()->toDateTimeString()], $this->actor()), 'visited_at');
        $this->refused(fn () => $this->svc()->recordVisit($p, ['urine_protein' => 'lots'], $this->actor()), 'urine_protein');
        $this->svc()->close($p, 'transferred', $this->actor());
        $this->refused(fn () => $this->svc()->recordVisit($p->refresh(), [], $this->actor()), 'status');
        $this->assertSame(0, MchAncVisit::count());
    }

    public function test_a_live_birth_opens_the_babys_record_and_a_birth_notification_and_ends_the_pregnancy(): void
    {
        $p = $this->book();
        $mother = $p->patient;

        $d = $this->svc()->deliver($p, ['mode' => 'normal', 'attendant_name' => 'Dr Ayesha', 'complications' => ['postpartum_haemorrhage', 'made_up']], [['sex' => 'female', 'weight_g' => 3100, 'apgar_1' => 8, 'apgar_5' => 9, 'name' => 'Noor']], $this->actor());

        $b = MchNewborn::firstOrFail();
        $this->assertSame('delivered', $p->refresh()->status);
        $this->assertSame(['postpartum_haemorrhage'], $d->complications);
        $this->assertNotNull($b->child_patient_id);
        $child = Patient::findOrFail($b->child_patient_id);
        $this->assertSame([$mother->last_name, $d->delivered_at->toDateString()], [$child->last_name, CarbonImmutable::parse($child->date_of_birth)->toDateString()]);
        $n = VrBirthNotification::findOrFail($b->birth_notification_id);
        $this->assertSame([$mother->id, $child->id, 'draft', 'normal'], [$n->mother_patient_id, $n->child_patient_id, $n->status, $n->delivery_type]);
    }

    public function test_a_stillbirth_opens_no_patient_record_but_is_still_notified(): void
    {
        $p = $this->book(null, ['lmp' => now()->subWeeks(34)->toDateString()]);

        $this->svc()->deliver($p, ['mode' => 'normal'], [['sex' => 'male', 'outcome' => 'stillbirth', 'weight_g' => 1800]], $this->actor());

        $b = MchNewborn::firstOrFail();
        $this->assertNull($b->child_patient_id);
        $this->assertSame('stillbirth', VrBirthNotification::findOrFail($b->birth_notification_id)->outcome);
    }

    public function test_twins_are_two_babies_with_their_order_and_nothing_is_written_when_one_is_wrong(): void
    {
        $p = $this->book();
        $this->refused(fn () => $this->svc()->deliver($p, ['mode' => 'normal'], [['sex' => 'female'], ['sex' => 'boy']], $this->actor()), 'babies');
        $this->refused(fn () => $this->svc()->deliver($p, ['mode' => 'normal'], [['sex' => 'female', 'apgar_1' => 11]], $this->actor()), 'babies');
        $this->refused(fn () => $this->svc()->deliver($p, ['mode' => 'flying'], [['sex' => 'female']], $this->actor()), 'mode');
        $this->refused(fn () => $this->svc()->deliver($p, ['mode' => 'normal'], [], $this->actor()), 'babies');
        $this->assertSame(0, MchNewborn::count());
        $this->assertSame('active', $p->refresh()->status);

        $this->svc()->deliver($p, ['mode' => 'caesarean'], [['sex' => 'female'], ['sex' => 'male']], $this->actor());

        $this->assertSame([1, 2], MchNewborn::orderBy('birth_order')->pluck('birth_order')->all());
        $this->assertSame([2, 2], [VrBirthNotification::first()->plurality, VrBirthNotification::count()]);
    }

    public function test_the_records_are_not_opened_when_the_caller_says_the_child_is_registered_elsewhere(): void
    {
        $p = $this->book();

        $this->svc()->deliver($p, ['mode' => 'normal'], [['sex' => 'female']], $this->actor(), openRecords: false);

        $this->assertSame([0, null], [VrBirthNotification::count(), MchNewborn::first()->child_patient_id]);
    }

    public function test_a_delivered_pregnancy_cannot_be_delivered_or_closed_again(): void
    {
        $p = $this->book();
        $this->svc()->deliver($p, ['mode' => 'normal'], [['sex' => 'female']], $this->actor());

        $this->refused(fn () => $this->svc()->deliver($p->refresh(), ['mode' => 'normal'], [['sex' => 'female']], $this->actor()), 'status');
        $this->refused(fn () => $this->svc()->close($p->refresh(), 'miscarriage', $this->actor()), 'end_reason');
    }
}
