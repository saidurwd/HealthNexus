<?php

namespace Tests\Feature\Ed;

use App\Models\Clinical\ClinicalAlert;
use App\Models\Ed\EdProtocolRun;
use App\Models\Ed\EdVisit;
use App\Models\Organization;
use App\Models\Patient;
use App\Services\Ed\EdMciService;
use App\Services\Ed\EdProtocolService;
use App\Services\Ed\EdTimerService;
use App\Services\Ed\EdTriageService;
use App\Services\Ed\EdVisitService;
use Illuminate\Validation\ValidationException;

class EdProtocolTest extends EdTestCase
{
    private function visit(): EdVisit
    {
        return app(EdVisitService::class)->arrive($this->user, $this->branch, ['patient_id' => $this->patient()->id, 'arrival_mode' => 'ambulance']);
    }

    public function test_starting_a_protocol_creates_timed_steps_from_config(): void
    {
        $run = app(EdProtocolService::class)->start($this->visit(), $this->user, 'stroke');

        $this->assertCount(count(config('ed_protocols.stroke.steps')), $run->steps);
        $ct = $run->steps->firstWhere('step_key', 'ct');
        $this->assertSame(25, (int) $run->started_at->diffInMinutes($ct->due_at));
    }

    public function test_the_same_protocol_cannot_run_twice_at_once(): void
    {
        $visit = $this->visit();
        app(EdProtocolService::class)->start($visit, $this->user, 'sepsis');
        $this->expectException(ValidationException::class);
        app(EdProtocolService::class)->start($visit, $this->user, 'sepsis');
    }

    public function test_the_run_completes_when_the_last_step_is_done(): void
    {
        $service = app(EdProtocolService::class);
        $run = $service->start($this->visit(), $this->user, 'acs');
        foreach ($run->steps as $step) {
            $service->completeStep($step, $this->user);
        }

        $this->assertSame(EdProtocolRun::COMPLETED, $run->fresh()->status);
    }

    public function test_a_step_is_done_once_and_a_second_tap_changes_nothing(): void
    {
        $service = app(EdProtocolService::class);
        $step = $service->start($this->visit(), $this->user, 'acs')->steps->first();
        $service->completeStep($step, $this->user, 'first');
        $doneAt = $step->fresh()->done_at;
        $service->completeStep($step, $this->staff(), 'second');

        $this->assertEquals($doneAt, $step->fresh()->done_at);
        $this->assertSame('first', $step->fresh()->note);
    }

    public function test_an_overdue_step_raises_one_alert(): void
    {
        app(EdProtocolService::class)->start($this->visit(), $this->user, 'trauma');
        $this->travel(20)->minutes();

        app(EdTimerService::class)->check();
        $this->assertSame(5, ClinicalAlert::where('category', 'ed_protocol_overdue')->count());
        app(EdTimerService::class)->check();
        $this->assertSame(5, ClinicalAlert::where('category', 'ed_protocol_overdue')->count());
    }

    public function test_injuries_are_validated_against_the_body_map(): void
    {
        $visit = $this->visit();
        $service = app(EdProtocolService::class);
        $service->recordInjury($visit, $this->user, ['body_region' => 'chest', 'injury_type' => 'fracture', 'severity' => 'severe']);
        $this->assertSame(1, $visit->injuries()->count());

        $this->expectException(ValidationException::class);
        $service->recordInjury($visit, $this->user, ['body_region' => 'elbow', 'injury_type' => 'fracture', 'severity' => 'severe']);
    }

    public function test_mci_mode_flags_new_arrivals_and_counts_by_level(): void
    {
        $mci = app(EdMciService::class);
        $mci->activate($this->user, $this->organization->id, $this->branch->id, 'Bus crash');
        $a = $this->visit();
        $b = app(EdVisitService::class)->arrive($this->user, $this->branch, ['arrival_mode' => 'ambulance']);
        app(EdTriageService::class)->triage($a, $this->user, ['level' => 1]);

        $this->assertTrue($a->fresh()->is_mass_casualty && $b->fresh()->is_mass_casualty);
        $this->assertSame(['1' => 1, 'untriaged' => 1], $mci->counts($this->organization->id, $this->branch->id));

        $mci->deactivate($this->user, $this->organization->id, $this->branch->id);
        $this->assertFalse(app(EdVisitService::class)->arrive($this->user, $this->branch, ['patient_id' => Patient::factory()->create(['organization_id' => $this->organization->id])->id])->is_mass_casualty);
    }

    public function test_screens_work_and_other_organization_steps_are_404(): void
    {
        $visit = $this->visit();
        $base = "/admin/emergency/visits/{$visit->public_id}";
        $this->post("$base/protocols", ['protocol' => 'stroke'])->assertRedirect();
        $step = EdProtocolRun::where('visit_id', $visit->id)->firstOrFail()->steps()->first();
        $this->post("/admin/emergency/protocol-steps/{$step->public_id}/done")->assertRedirect();
        $this->post("$base/injuries", ['body_region' => 'head', 'injury_type' => 'laceration', 'severity' => 'minor'])->assertRedirect();
        $this->post('/admin/emergency/mci', ['action' => 'activate'])->assertRedirect();
        $this->get($base)->assertOk()->assertSee('Blood glucose checked')->assertSee('Laceration');
        $this->get('/admin/emergency')->assertOk()->assertSee('Mass-casualty mode is on');

        $step->update(['organization_id' => Organization::factory()->create()->id]);
        $this->post("/admin/emergency/protocol-steps/{$step->public_id}/done")->assertNotFound();
    }
}
