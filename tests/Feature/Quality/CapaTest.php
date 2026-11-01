<?php

namespace Tests\Feature\Quality;

use App\Models\Organization;
use App\Models\Quality\QaAction;
use App\Services\Quality\ActionService;
use App\Services\Quality\IncidentService;
use App\Services\Quality\InvestigationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CapaTest extends QualityTestCase
{
    private function serious(): array
    {
        $m = $this->manager();
        $i = $this->incident($this->actor(['quality.incident.report']), ['harm' => 'major', 'category' => 'procedure']);
        $i = app(IncidentService::class)->triage($i, $m, null, $m);

        return [$i, $m];
    }

    public function test_a_serious_incident_needs_an_accepted_investigation_before_it_closes(): void
    {
        [$i, $m] = $this->serious();
        $this->assertTrue(app(IncidentService::class)->needsInvestigation($i));
        $inv = app(InvestigationService::class);
        $lead = $this->actor(['quality.investigation.manage']);
        $investigation = $inv->start($i, $lead, 'five_whys', $m);
        $this->assertSame('investigating', $i->fresh()->status);
        try {
            app(IncidentService::class)->close($i->fresh(), $m, 'Closing without finishing the work');
            $this->fail('cannot close while investigating');
        } catch (ValidationException) {
        }
        $inv->save($investigation, 'The handover did not mention the allergy and the list was on paper only.', [['category' => 'communication', 'text' => 'Handover is verbal'], ['category' => 'process', 'text' => 'No allergy field on the theatre list']]);
        $inv->submit($investigation->fresh(), $lead);
        try {
            $inv->review($investigation->fresh(), $lead, true, null);
            $this->fail('the lead cannot accept their own investigation');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('second person', implode(' ', $e->errors()['status']));
        }
        $inv->review($investigation->fresh(), $m, true, null);
        $this->assertSame('actioned', $i->fresh()->status);
        $closed = app(IncidentService::class)->close($i->fresh(), $m, 'Investigation accepted and the handover form changed');
        $this->assertSame('closed', $closed->status);
    }

    public function test_causes_name_systems_and_a_hand_in_needs_findings(): void
    {
        [$i, $m] = $this->serious();
        $inv = app(InvestigationService::class);
        $x = $inv->start($i, $m, 'fishbone', $m);
        try {
            $inv->save($x, 'Findings that are long enough to count.', [['category' => 'a_person', 'text' => 'Nurse X']]);
            $this->fail('a cause category must be a system category');
        } catch (ValidationException) {
        }
        try {
            $inv->submit($x->fresh(), $m);
            $this->fail('nothing to hand in');
        } catch (ValidationException) {
        }
        $this->assertTrue(true);
    }

    public function test_an_investigation_sent_back_returns_to_its_lead(): void
    {
        [$i, $m] = $this->serious();
        $inv = app(InvestigationService::class);
        $lead = $this->actor(['quality.investigation.manage']);
        $x = $inv->start($i, $lead, 'timeline', $m);
        $inv->save($x, 'A timeline with enough words in it to be accepted.', [['category' => 'staffing', 'text' => 'One nurse for twelve patients']]);
        $inv->submit($x->fresh(), $lead);
        $back = $inv->review($x->fresh(), $m, false, 'The timeline stops at midnight');
        $this->assertSame('returned', $back->status);
        $inv->save($back, 'A longer timeline that now covers the whole night.', [['category' => 'staffing', 'text' => 'One nurse for twelve patients']]);
        $this->assertSame('submitted', $inv->submit($back->fresh(), $lead)->status);
    }

    public function test_an_action_is_done_by_its_owner_and_checked_by_someone_else(): void
    {
        [$i, $m] = $this->serious();
        $owner = $this->actor(['quality.action.view']);
        $svc = app(ActionService::class);
        $a = $svc->create($this->organization->id, ['source_type' => 'incident', 'source_id' => $i->id, 'action_type' => 'preventive', 'description' => 'Add an allergy field to the theatre list', 'owner_id' => $owner->id, 'due_on' => '2026-09-30'], $m);
        $this->assertMatchesRegularExpression('/^CAPA-2026-\d{5}$/', $a->number);
        $svc->start($a, $owner);
        try {
            $svc->verify($a->fresh(), $m, true, null);
            $this->fail('not done yet');
        } catch (ValidationException) {
        }
        $svc->complete($a->fresh(), $owner, 'The new list is in use in all three theatres');
        try {
            $svc->verify($a->fresh(), $owner, true, null);
            $this->fail('the owner cannot check their own work');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('second person', implode(' ', $e->errors()['status']));
        }
        $this->assertSame('verified', $svc->verify($a->fresh(), $m, true, 'Seen in use on 3 lists')->status);
    }

    public function test_an_action_that_did_not_work_goes_back_to_its_owner(): void
    {
        [$i, $m] = $this->serious();
        $owner = $this->actor(['quality.action.view']);
        $svc = app(ActionService::class);
        $a = $svc->create($this->organization->id, ['source_type' => 'incident', 'source_id' => $i->id, 'action_type' => 'corrective', 'description' => 'Retrain staff on the handover form', 'owner_id' => $owner->id], $m);
        $svc->complete($a, $owner, 'Training held on Sunday');
        $back = $svc->verify($a->fresh(), $m, false, 'Only 3 of 20 staff came');
        $this->assertSame('open', $back->status);
        $this->assertFalse($back->effective);
        $this->assertNull($back->done_at);
    }

    public function test_dates_owners_and_sources_are_checked(): void
    {
        [$i, $m] = $this->serious();
        $svc = app(ActionService::class);
        $base = ['source_type' => 'incident', 'source_id' => $i->id, 'action_type' => 'corrective', 'description' => 'Something to do', 'owner_id' => $m->id];
        foreach ([['due_on' => '2026-09-01'], ['owner_id' => 999999], ['source_id' => 999999], ['action_type' => 'blame'], ['description' => 'x']] as $bad) {
            try {
                $svc->create($this->organization->id, $bad + $base, $m);
                $this->fail('accepted '.json_encode($bad));
            } catch (ValidationException) {
            }
        }
        $other = Organization::factory()->create();
        $stranger = $this->actor([]);
        DB::table('user_organizations')->where('user_id', $stranger->id)->delete();
        $this->expectException(ValidationException::class);
        $svc->create($this->organization->id, ['owner_id' => $stranger->id] + $base, $m);
        $this->assertNotNull($other);
    }

    public function test_overdue_actions_warn_the_owner_once(): void
    {
        [$i, $m] = $this->serious();
        $owner = $this->actor(['quality.action.view']);
        $svc = app(ActionService::class);
        $a = $svc->create($this->organization->id, ['source_type' => 'incident', 'source_id' => $i->id, 'action_type' => 'corrective', 'description' => 'Replace the cracked tile', 'owner_id' => $owner->id, 'due_on' => '2026-09-12'], $m);
        $this->assertSame(0, $svc->alertOverdue($this->organization->id));
        Carbon::setTestNow('2026-09-14 10:00:00');
        $this->assertSame(1, $svc->alertOverdue($this->organization->id));
        $this->assertSame(0, $svc->alertOverdue($this->organization->id), 'told once');
        $this->assertNotNull(QaAction::find($a->id)->overdue_alerted_at);
    }

    public function test_actions_are_not_added_to_a_closed_incident(): void
    {
        $m = $this->manager();
        $svc = app(IncidentService::class);
        $i = $svc->close($svc->triage($this->incident($this->actor(['quality.incident.report'])), $m, null, $m), $m, 'Reviewed and nothing more to do');
        $this->expectException(ValidationException::class);
        app(ActionService::class)->create($this->organization->id, ['source_type' => 'incident', 'source_id' => $i->id, 'action_type' => 'corrective', 'description' => 'Too late for this', 'owner_id' => $m->id], $m);
    }
}
