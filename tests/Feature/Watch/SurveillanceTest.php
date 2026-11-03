<?php

namespace Tests\Feature\Watch;

use App\Models\Branch;
use App\Models\Geo\GeoDistrict;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\Watch\SvCondition;
use App\Models\Watch\SvSignal;
use App\Services\Geo\GeoInstaller;
use App\Services\Watch\SurveillanceService;
use App\Services\Watch\WatchInstaller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SurveillanceTest extends WatchTestCase
{
    private function svc(): SurveillanceService
    {
        return app(SurveillanceService::class);
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

    public function test_a_case_is_a_distinct_patient_with_a_matching_diagnosis_in_the_window_not_ruled_out(): void
    {
        $c = $this->condition();
        $p = $this->diagnose('A90', 1);
        $this->diagnose('A91.0', 2, $p);                      // the same patient twice: one case
        $this->diagnose('a90', 3);                             // lower case still counts
        $this->diagnose('A90', 2, null, 'rule_out');           // ruled out
        $this->diagnose('A92.0', 2);                           // another disease
        $this->diagnose('A90', 9);                             // before the window
        [$from, $to] = $this->svc()->window($c, $this->now);

        $this->assertSame(2, $this->svc()->cases($c, $from, $to)->count());
        $this->assertSame(['2026-11-06', '2026-11-12'], [$from->toDateString(), $to->toDateString()]);
    }

    public function test_the_day_edges_are_dhaka_days(): void
    {
        $c = $this->condition(['window_days' => 3]);
        $this->diagnose('A90', 2);                              // 10:00 Dhaka on 10 Nov: inside
        DB::table('diagnoses')->insert(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $this->patient()->id, 'doctor_id' => $this->doctor->id, 'code' => 'A90', 'description' => 'x', 'status' => 'confirmed',
            'recorded_at' => '2026-11-09 17:59:00', 'created_at' => $this->now, 'updated_at' => $this->now]);   // 23:59 Dhaka on 9 Nov: the day before the window
        [$from, $to] = $this->svc()->window($c, $this->now);

        $this->assertSame(1, $this->svc()->cases($c, $from, $to)->count());
    }

    public function test_nothing_is_raised_below_the_minimum(): void
    {
        $this->condition();
        $this->diagnose('A90', 1);
        $this->diagnose('A90', 2);

        $this->assertSame(['raised' => 0, 'updated' => 0], $this->svc()->scan($this->organization->id, $this->now));
        $this->assertSame(0, SvSignal::count());
    }

    public function test_a_condition_never_seen_before_raises_a_first_cases_signal_at_the_minimum(): void
    {
        $c = $this->condition();
        foreach ([0, 1, 2] as $d) {
            $this->diagnose('A90', $d);
        }

        $r = $this->svc()->scan($this->organization->id, $this->now);

        $s = SvSignal::firstOrFail();
        $this->assertSame(1, $r['raised']);
        $this->assertSame([$c->id, 3, 'first', 'new', null], [$s->condition_id, $s->cases, $s->reason, $s->status, $s->district_id]);
        $this->assertSame('2026-11-09', $s->week_start->toDateString(), 'the Monday of the week');
    }

    public function test_a_rise_must_pass_the_usual_by_the_factor_to_raise_a_spike(): void
    {
        $this->condition();
        for ($w = 1; $w <= 8; $w++) {          // the usual: 2 patients in each earlier week
            $this->diagnose('A90', 7 * $w + 1);
            $this->diagnose('A90', 7 * $w + 2);
        }
        foreach ([0, 1, 2] as $d) {            // this week: 3, which is above the minimum but under twice the usual (4)
            $this->diagnose('A90', $d);
        }

        $this->assertSame(0, $this->svc()->scan($this->organization->id, $this->now)['raised'], '3 is not twice 2');

        $this->diagnose('A90', 3);             // 4: twice the usual
        $r = $this->svc()->scan($this->organization->id, $this->now);

        $s = SvSignal::firstOrFail();
        $this->assertSame(1, $r['raised']);
        $this->assertSame([4, 'spike', '2.0'], [$s->cases, $s->reason, (string) $s->baseline]);
    }

    public function test_a_cluster_in_one_district_raises_its_own_signal(): void
    {
        app(GeoInstaller::class)->install();
        [$dhaka, $sylhet] = [GeoDistrict::where('name_en', 'Dhaka')->value('id'), GeoDistrict::where('name_en', 'Sylhet')->value('id')];
        $this->condition(['min_cases' => 50]);       // no hospital-wide signal
        foreach ([0, 1, 2] as $d) {
            $this->diagnose('A90', $d, $this->live($dhaka));
        }
        $this->diagnose('A90', 1, $this->live($sylhet));
        $this->diagnose('A90', 1);                    // no district recorded

        $this->svc()->scan($this->organization->id, $this->now);

        $s = SvSignal::firstOrFail();
        $this->assertSame([1, 'cluster', $dhaka, 3], [SvSignal::count(), $s->reason, $s->district_id, $s->cases]);
    }

    public function test_scanning_again_the_same_week_does_not_repeat_a_signal_but_a_new_signal_grows_with_the_cases(): void
    {
        $this->condition();
        foreach ([0, 1, 2] as $d) {
            $this->diagnose('A90', $d);
        }
        $this->svc()->scan($this->organization->id, $this->now);
        $again = $this->svc()->scan($this->organization->id, $this->now);
        $this->assertSame([0, 0, 1], [$again['raised'], $again['updated'], SvSignal::count()]);

        $this->diagnose('A90', 0);
        $grown = $this->svc()->scan($this->organization->id, $this->now);

        $this->assertSame([0, 1, 4], [$grown['raised'], $grown['updated'], SvSignal::firstOrFail()->cases]);
    }

    public function test_an_inactive_condition_is_not_watched_and_another_hospital_is_not_counted(): void
    {
        $this->condition(['active' => false]);
        foreach ([0, 1, 2] as $d) {
            $this->diagnose('A90', $d);
        }
        $this->assertSame(0, $this->svc()->scan($this->organization->id, $this->now)['raised']);

        $c = $this->condition(['code' => 'again', 'active' => true]);
        $other = Organization::factory()->create();
        DB::table('diagnoses')->insert(['organization_id' => $other->id, 'branch_id' => Branch::factory()->create(['organization_id' => $other->id])->id, 'patient_id' => Patient::factory()->create(['organization_id' => $other->id])->id,
            'doctor_id' => $this->doctor->id, 'code' => 'A90', 'description' => 'x', 'status' => 'confirmed', 'recorded_at' => $this->now, 'created_at' => $this->now, 'updated_at' => $this->now]);
        [$f, $t] = $this->svc()->window($c, $this->now);
        $this->assertSame(3, $this->svc()->cases($c, $f, $t)->count());
    }

    public function test_a_signal_is_taken_up_decided_with_a_reason_and_only_a_confirmed_one_is_reported(): void
    {
        $s = SvSignal::factory()->create(['organization_id' => $this->organization->id, 'condition_id' => $this->condition()->id]);
        $by = $this->actor();

        $this->refused(fn () => $this->svc()->report($s, 'IEDCR', null, $by), 'status');
        $this->svc()->investigate($s, $by);
        $this->refused(fn () => $this->svc()->investigate($s->refresh(), $by), 'status');
        $this->refused(fn () => $this->svc()->decide($s->refresh(), true, 'ok', $by), 'decision_note');
        $this->svc()->decide($s->refresh(), true, 'Eight cases from one ward area, a real rise.', $by);
        $this->refused(fn () => $this->svc()->decide($s->refresh(), false, 'Changed my mind later.', $by), 'status');
        $this->refused(fn () => $this->svc()->report($s->refresh(), ' ', null, $by), 'reported_to');

        $r = $this->svc()->report($s->refresh(), 'Civil Surgeon office', 'CS/2026/114', $by);

        $this->assertSame(['confirmed', 'Civil Surgeon office', 'CS/2026/114'], [$r->status, $r->reported_to, $r->report_reference]);
        $this->refused(fn () => $this->svc()->report($r->refresh(), 'IEDCR', null, $by), 'status');
    }

    public function test_a_dismissed_signal_cannot_be_reported(): void
    {
        $s = SvSignal::factory()->create(['organization_id' => $this->organization->id, 'condition_id' => $this->condition()->id]);
        $this->svc()->decide($s, false, 'A batch of old results entered late.', $this->actor());

        $this->refused(fn () => $this->svc()->report($s->refresh(), 'IEDCR', null, $this->actor()), 'status');
    }

    public function test_condition_numbers_are_checked(): void
    {
        $c = $this->condition();
        foreach ([['window_days' => 1], ['min_cases' => 0], ['spike_factor' => 0.5], ['severity' => 'meh']] as $bad) {
            $this->refused(fn () => $this->svc()->updateCondition($c, $bad), 'window_days');
        }
        $this->svc()->updateCondition($c, ['min_cases' => 5, 'severity' => 'critical', 'active' => false]);

        $this->assertSame([5, 'critical', false], [$c->refresh()->min_cases, $c->severity, $c->active]);
    }

    public function test_the_starting_list_is_added_once_and_never_overwrites_an_edit(): void
    {
        $this->assertSame(count(config('watch.conditions')), app(WatchInstaller::class)->install($this->organization->id)['conditions']);
        $dengue = SvCondition::where(['organization_id' => $this->organization->id, 'code' => 'dengue'])->firstOrFail();
        $dengue->update(['min_cases' => 9]);

        $this->assertSame(0, app(WatchInstaller::class)->install($this->organization->id)['conditions']);
        $this->assertSame(9, $dengue->refresh()->min_cases);
    }
}
