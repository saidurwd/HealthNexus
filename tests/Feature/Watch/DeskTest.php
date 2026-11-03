<?php

namespace Tests\Feature\Watch;

use App\Models\Organization;
use App\Models\Watch\SvSignal;
use App\Services\Watch\WatchOverview;
use App\Support\Dashboard\DashboardPeriod;
use Illuminate\Support\Facades\Artisan;

class DeskTest extends WatchTestCase
{
    public function test_every_screen_needs_its_own_right(): void
    {
        $nobody = $this->actor(['patients.view']);
        foreach (['/admin/watch', '/admin/watch/signals', '/admin/watch/conditions'] as $url) {
            $this->as($nobody)->get($url)->assertForbidden();
        }
        $this->as($this->actor(['watch.signal.view']))->get('/admin/watch')->assertForbidden();
        $this->as($this->actor(['watch.dashboard.view']))->get('/admin/watch/signals')->assertForbidden();
    }

    public function test_an_officer_looks_decides_and_reports_from_the_screens(): void
    {
        $this->condition();
        foreach ([0, 1, 2] as $d) {
            $this->diagnose('A90', $d);
        }
        $officer = $this->actor(['watch.dashboard.view', 'watch.signal.view', 'watch.signal.decide', 'watch.signal.report']);

        $this->as($officer)->post('/admin/watch/scan')->assertRedirect('/admin/watch/signals');
        $s = SvSignal::firstOrFail();
        $this->as($officer)->get('/admin/watch/signals')->assertOk()->assertSee('Dengue');
        $this->as($officer)->post("/admin/watch/signals/{$s->public_id}/decide", ['outcome' => 'confirm', 'decision_note' => 'A real rise in the ward area.'])->assertSessionHas('success');
        $this->as($officer)->post("/admin/watch/signals/{$s->public_id}/report", ['reported_to' => 'IEDCR', 'report_reference' => 'R-1'])->assertSessionHas('success');

        $this->assertSame(['confirmed', 'IEDCR'], [$s->refresh()->status, $s->reported_to]);
    }

    public function test_a_viewer_cannot_decide_or_report_or_scan(): void
    {
        $s = SvSignal::factory()->create(['organization_id' => $this->organization->id, 'condition_id' => $this->condition()->id]);
        $viewer = $this->actor(['watch.dashboard.view', 'watch.signal.view']);

        $this->as($viewer)->post("/admin/watch/signals/{$s->public_id}/decide", ['outcome' => 'confirm', 'decision_note' => 'A real rise.'])->assertForbidden();
        $this->as($viewer)->post("/admin/watch/signals/{$s->public_id}/report", ['reported_to' => 'IEDCR'])->assertForbidden();
        $this->as($viewer)->post('/admin/watch/scan')->assertForbidden();
        $this->as($viewer)->post('/admin/watch/conditions/install')->assertForbidden();
        $this->as($viewer)->get("/admin/watch/signals/{$s->public_id}")->assertOk()->assertDontSee('Save the decision');
    }

    public function test_another_hospitals_signal_is_not_found(): void
    {
        $other = Organization::factory()->create();
        $theirs = SvSignal::factory()->create(['organization_id' => $other->id]);

        $this->as($this->actor(['watch.signal.view']))->get("/admin/watch/signals/{$theirs->public_id}")->assertNotFound();
    }

    public function test_the_manager_edits_a_condition_and_the_viewer_cannot(): void
    {
        $c = $this->condition();
        $this->as($this->actor(['watch.signal.view']))->post("/admin/watch/conditions/{$c->id}", ['window_days' => 7, 'min_cases' => 9, 'spike_factor' => 2, 'cluster_cases' => 3, 'severity' => 'warning'])->assertForbidden();

        $this->as($this->actor(['watch.signal.view', 'watch.condition.manage']))->post("/admin/watch/conditions/{$c->id}", ['window_days' => 7, 'min_cases' => 9, 'spike_factor' => 2, 'cluster_cases' => 3, 'severity' => 'critical'])->assertSessionHas('success');

        $this->assertSame([9, 'critical'], [$c->refresh()->min_cases, $c->severity]);
    }

    public function test_the_overview_counts_waiting_signals_and_the_patients_of_this_hospital_only(): void
    {
        $c = $this->condition();
        SvSignal::factory()->create(['organization_id' => $this->organization->id, 'condition_id' => $c->id, 'status' => 'new', 'week_start' => '2026-11-09']);
        SvSignal::factory()->create(['organization_id' => $this->organization->id, 'condition_id' => $c->id, 'status' => 'investigating', 'week_start' => '2026-11-02']);
        SvSignal::factory()->create(['organization_id' => $this->organization->id, 'condition_id' => $c->id, 'status' => 'confirmed', 'week_start' => '2026-10-26', 'decided_at' => $this->now]);
        SvSignal::factory()->create(['organization_id' => Organization::factory()->create()->id, 'status' => 'new']);
        foreach ([1, 2] as $d) {
            $this->diagnose('A90', $d);
        }
        $this->diagnose('I10', 1);

        $s = app(WatchOverview::class)->summary($this->organization->id, null, DashboardPeriod::make('7d', now: $this->now), $this->now);

        $this->assertSame([1, 1, 1, 1], [$s['new'], $s['investigating'], $s['to_report'], $s['watched']]);
        $this->assertSame([2, 3, 1], [$s['current']['patients'], $s['current']['signals'], $s['current']['confirmed']]);
        $this->assertSame([['condition' => 'Dengue', 'cases' => 2]], $s['by_condition']);
    }

    public function test_the_overview_page_states_its_question(): void
    {
        $html = $this->as($this->actor(['watch.dashboard.view']))->get('/admin/watch')->assertOk()->getContent();

        $this->assertStringContainsString('data-dashboard-question', $html);
        $this->assertStringContainsString('Confirmed, not yet reported', $html);
    }

    public function test_the_scan_command_covers_only_hospitals_with_the_module_on(): void
    {
        $this->condition();
        foreach ([0, 1, 2] as $d) {
            $this->diagnose('A90', $d);
        }

        $this->assertSame(0, Artisan::call('watch:scan'));
        $this->assertSame(0, Artisan::call('watch:install'));
    }
}
