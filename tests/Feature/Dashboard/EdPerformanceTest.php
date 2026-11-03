<?php

namespace Tests\Feature\Dashboard;

use App\Models\Patient;
use App\Models\User;
use App\Services\Ed\EdPerformance;
use App\Services\SettingsService;
use App\Support\Dashboard\DashboardPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/** Emergency performance on the dashboard standard. Now is Thursday 12 Nov 2026, 10:00 in Dhaka (04:00 UTC). */
class EdPerformanceTest extends FrontDoorTestCase
{
    private function visit(string $arrivedUtc, array $extra = []): int
    {
        $at = CarbonImmutable::parse($arrivedUtc, 'UTC');

        return $this->row('ed_visits', $extra + ['visit_number' => 'V-'.uniqid(), 'patient_id' => 1, 'arrival_at' => $arrivedUtc, 'status' => 'dispositioned', 'triaged_at' => $at->addMinutes(5), 'doctor_due_at' => $at->addMinutes(30), 'doctor_seen_at' => $at->addMinutes(20)]);
    }

    private function user(): User
    {
        return $this->actor(['ed.kpi.view']);
    }

    private function summary(string $key = '7d'): array
    {
        return app(EdPerformance::class)->summary($this->organization->id, $this->branch->id, DashboardPeriod::make($key, null, null, '7d', null, CarbonImmutable::parse('2026-11-12 10:00:00', 'Asia/Dhaka')));
    }

    public function test_it_states_itself_defaults_to_seven_days_and_is_refused_without_the_right(): void
    {
        $html = $this->assertDashboardStatesItself('/emergency/dashboard', $this->user());
        $this->assertStringContainsString('Is the emergency department meeting its targets?', $html);
        $this->assertStringContainsString('Last 7 days', $html);
        $this->assertStringContainsString('No arrivals in this period.', $html);
        $this->assertDashboardRefuses('/emergency/dashboard', $this->actor(['ed.board.view']));
    }

    public function test_the_old_days_link_still_means_the_same_period(): void
    {
        $this->as($this->user())->get('/emergency/dashboard?days=30')->assertOk()->assertSee('Last 30 days')->assertViewHas('days', 30);
        $this->as($this->user())->get('/emergency/dashboard?days=1')->assertOk()->assertSee('Today');
    }

    public function test_times_to_triage_and_to_the_doctor_are_medians_with_the_ninetieth_percentile(): void
    {
        foreach ([10, 20, 30, 40, 100] as $minutes) {
            $this->visit('2026-11-10 03:00:00', ['doctor_seen_at' => CarbonImmutable::parse('2026-11-10 03:00:00')->addMinutes($minutes)]);
        }

        $k = $this->summary()['kpi'];

        $this->assertSame(['median' => 30, 'p90' => 100], $k['door_to_doctor']);
        $this->assertSame(5, $k['arrivals']);
        $this->assertSame(['median' => 5, 'p90' => 5], $k['door_to_triage']);
        $this->assertSame(60.0, $k['door_to_doctor_met_percent'], 'seen within 30 minutes: 10, 20 and 30 of 5');
    }

    public function test_the_period_is_compared_with_the_period_before(): void
    {
        $this->visit('2026-11-10 03:00:00');
        $this->visit('2026-11-09 03:00:00');
        foreach (range(1, 4) as $i) {
            $this->visit('2026-11-03 03:00:00');   // the seven days before
        }

        $s = $this->summary('7d');

        $this->assertSame([2, 4], [$s['kpi']['arrivals'], $s['previous']['arrivals']]);
        $html = $this->as($this->user())->get('/emergency/dashboard')->getContent();
        $this->assertStringContainsString('−50%', $html);
        $this->assertStringContainsString('vs the period before', $html);
    }

    public function test_arrivals_by_hour_use_dhaka_time_and_by_day_fill_empty_days(): void
    {
        $this->visit('2026-11-11 19:00:00');   // 01:00 Dhaka on 12 Nov
        $this->visit('2026-11-11 19:30:00');
        $this->visit('2026-11-12 03:10:00');   // 09:10 Dhaka

        $s = $this->summary('7d');

        $this->assertSame(24, count($s['by_hour']));
        $this->assertSame([2, 1], [$s['by_hour'][1]['arrivals'], $s['by_hour'][9]['arrivals']]);
        $this->assertSame(7, count($s['per_day']));
        $this->assertSame(3, $s['per_day'][6]['arrivals'], 'all three arrived on 12 November in Dhaka');
        $this->assertSame(0, $s['per_day'][2]['arrivals']);
    }

    public function test_left_without_being_seen_and_outcomes(): void
    {
        $this->visit('2026-11-10 03:00:00', ['dispositioned_at' => '2026-11-10 05:00:00', 'disposition_type' => 'lwbs', 'doctor_seen_at' => null]);
        $this->visit('2026-11-10 03:00:00', ['dispositioned_at' => '2026-11-10 06:00:00', 'disposition_type' => 'discharged']);
        $this->visit('2026-11-10 03:00:00', ['dispositioned_at' => '2026-11-10 07:00:00', 'disposition_type' => 'discharged']);
        $this->visit('2026-11-10 03:00:00', ['dispositioned_at' => '2026-11-10 08:00:00', 'disposition_type' => 'admitted']);

        $k = $this->summary()['kpi'];

        $this->assertSame(25.0, $k['lwbs_percent']);
        $this->assertSame(['admitted' => 1, 'discharged' => 2, 'lwbs' => 1], $k['by_disposition']);
    }

    public function test_targets_are_the_hospitals_and_colour_comes_with_an_icon_and_words(): void
    {
        $this->visit('2026-11-10 03:00:00', ['doctor_seen_at' => '2026-11-10 03:50:00']);   // 50 minutes
        $user = $this->user();
        $this->assertStringNotContainsString('Off target', $this->as($user)->get('/emergency/dashboard')->getContent(), 'no target ships');

        app(SettingsService::class)->set('dashboard.target.ed_door_to_doctor', '30');
        Cache::flush();
        $html = $this->as($user)->get('/emergency/dashboard')->getContent();
        $this->assertStringContainsString('Off target', $html);
        $this->assertStringContainsString('bi-x-circle', $html);
    }

    public function test_another_hospitals_visits_are_not_counted_and_no_patient_is_named(): void
    {
        $other = $this->otherHospital();
        $this->row('ed_visits', ['organization_id' => $other->id, 'visit_number' => 'F-1', 'patient_id' => 1, 'arrival_at' => '2026-11-10 03:00:00', 'status' => 'dispositioned']);
        $p = Patient::factory()->create(['organization_id' => $this->organization->id, 'first_name' => 'Zubaida']);
        $this->visit('2026-11-10 03:00:00', ['patient_id' => $p->id]);

        $html = $this->as($this->user())->get('/emergency/dashboard')->getContent();

        $this->assertSame(1, $this->summary()['kpi']['arrivals']);
        $this->assertNoPatientIdentity($html, ['Zubaida']);
    }

    public function test_a_custom_period_is_honoured(): void
    {
        $this->visit('2026-10-20 03:00:00');
        $html = $this->as($this->user())->get('/emergency/dashboard?period=custom&from=2026-10-19&to=2026-10-21')->assertOk()->getContent();
        $this->assertStringContainsString('3', $html);
        $this->assertSame(1, $this->as($this->user())->get('/emergency/dashboard?period=custom&from=2026-10-19&to=2026-10-21')->viewData('kpi')['arrivals']);
    }
}
