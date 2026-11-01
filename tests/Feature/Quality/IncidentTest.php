<?php

namespace Tests\Feature\Quality;

use App\Models\AuditLog;
use App\Models\Quality\QaIncident;
use App\Models\Quality\QaIncidentAmendment;
use App\Models\Quality\QaIncidentEvent;
use App\Models\Quality\QaIncidentReporter;
use App\Services\Quality\ActionService;
use App\Services\Quality\IncidentService;
use App\Services\Quality\IncidentSweep;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class IncidentTest extends QualityTestCase
{
    public function test_a_named_report_gets_a_number_a_due_date_and_a_protected_reporter(): void
    {
        $nurse = $this->actor(['quality.incident.report']);
        $i = $this->incident($nurse);
        $this->assertMatchesRegularExpression('/^INC-2026-\d{5}$/', $i->number);
        $this->assertSame('2026-10-10', $i->due_on->toDateString(), 'minor harm: 30 days');
        $this->assertFalse($i->is_anonymous);
        $this->assertSame($nurse->id, QaIncidentReporter::query()->where('incident_id', $i->id)->value('user_id'));
        $this->assertNull(QaIncidentEvent::query()->where(['incident_id' => $i->id, 'event' => 'reported'])->value('user_id'), 'the first event does not name the reporter');
        $this->assertNotSame($i->number, $this->incident($nurse)->number);
    }

    public function test_an_anonymous_report_leaves_nothing_that_names_the_reporter(): void
    {
        $nurse = $this->actor(['quality.incident.report']);
        $this->actingAs($nurse);
        $before = AuditLog::query()->count();
        $i = $this->incident($nurse, ['anonymous' => true]);
        $this->assertTrue($i->is_anonymous);
        $this->assertSame(0, QaIncidentReporter::query()->where('incident_id', $i->id)->count());
        $this->assertSame($before, AuditLog::query()->count(), 'no audit row names the user');
        $this->assertSame(0, QaIncidentEvent::query()->where('incident_id', $i->id)->whereNotNull('user_id')->count());
        $this->assertNull(app(IncidentService::class)->reveal($i, $this->manager(), 'A safeguarding concern needs a follow-up'), 'there is no one to reveal');
    }

    public function test_what_was_reported_cannot_be_changed_or_deleted_by_the_application_or_by_sql(): void
    {
        $i = $this->incident($this->actor(['quality.incident.report']));
        try {
            $i->update(['description' => 'Rewritten history']);
            $this->fail('the model should refuse');
        } catch (\LogicException) {
        }
        foreach (['description' => 'Rewritten', 'harm' => 'none', 'patient_id' => 1] as $col => $val) {
            try {
                DB::table('qa_incidents')->where('id', $i->id)->update([$col => $val]);
                $this->fail("raw SQL changed $col");
            } catch (\Throwable $e) {
                $this->assertStringContainsString('cannot be changed', $e->getMessage());
            }
        }
        $this->expectException(\Throwable::class);
        DB::table('qa_incidents')->where('id', $i->id)->delete();
    }

    public function test_the_trails_only_grow(): void
    {
        $i = $this->incident($this->actor(['quality.incident.report']));
        $e = QaIncidentEvent::query()->where('incident_id', $i->id)->first();
        foreach ([fn () => DB::table('qa_incident_events')->where('id', $e->id)->update(['note' => 'x']), fn () => DB::table('qa_incident_events')->where('id', $e->id)->delete()] as $try) {
            try {
                $try();
                $this->fail('a trail row was changed');
            } catch (\Throwable $ex) {
                $this->assertStringContainsString('only grows', $ex->getMessage());
            }
        }
    }

    public function test_workflow_fields_move_while_the_report_stays(): void
    {
        $m = $this->manager();
        $i = $this->incident($this->actor(['quality.incident.report']));
        $svc = app(IncidentService::class);
        $i = $svc->triage($i, $m, 'moderate', $m);
        $this->assertSame('triaged', $i->status);
        $this->assertSame('minor', $i->harm, 'as reported');
        $this->assertSame('moderate', $i->harmNow(), 'after review');
        $this->assertSame($m->id, $i->owner_id);
    }

    public function test_a_correction_is_an_amendment_and_the_original_stays(): void
    {
        $nurse = $this->actor(['quality.incident.report']);
        $i = $this->incident($nurse);
        app(IncidentService::class)->amend($i, $nurse, 'category', 'medication', 'It was a medicine error, not a fall');
        $i = $i->fresh('amendments');
        $this->assertSame('fall', $i->category);
        $this->assertSame('medication', $i->effective('category'));
        $a = QaIncidentAmendment::query()->first();
        $this->assertSame('fall', $a->old_value);
        $this->assertSame('medication', $a->new_value);
        $this->expectException(ValidationException::class);
        app(IncidentService::class)->amend($i, $nurse, 'number', 'INC-1', 'Trying to change the number');
    }

    public function test_revealing_the_reporter_needs_a_reason_and_is_recorded(): void
    {
        $nurse = $this->actor(['quality.incident.report']);
        $m = $this->manager();
        $this->actingAs($m);
        $i = $this->incident($nurse);
        try {
            app(IncidentService::class)->reveal($i, $m, 'why not');
            $this->fail('a short reason must be refused');
        } catch (ValidationException) {
        }
        $this->assertSame($nurse->id, app(IncidentService::class)->reveal($i, $m, 'We need to ask what the floor looked like')->id);
        $this->assertSame(1, QaIncidentEvent::query()->where(['incident_id' => $i->id, 'event' => 'revealed', 'user_id' => $m->id])->count());
        $this->assertSame(1, AuditLog::query()->where('action', 'REVEAL')->count());
    }

    public function test_a_serious_incident_alerts_the_quality_team_and_a_catastrophe_is_a_sentinel_event(): void
    {
        $m = $this->manager();
        $i = $this->incident($this->actor(['quality.incident.report']), ['harm' => 'catastrophic', 'kind' => 'incident', 'category' => 'procedure']);
        $this->assertSame('sentinel', $i->kind);
        $alert = DB::table('clinical_alerts')->where('category', 'quality')->first();
        $this->assertNotNull($alert, 'an alert was raised');
        $this->assertSame('critical', $alert->severity);
        $this->assertNotNull($m);
    }

    public function test_a_near_miss_has_no_harm_and_the_future_is_refused(): void
    {
        $i = $this->incident(null, ['kind' => 'near_miss', 'harm' => 'major']);
        $this->assertSame('none', $i->harm);
        $this->expectException(ValidationException::class);
        $this->incident(null, ['occurred_at' => '2026-09-11 10:00']);
    }

    public function test_it_cannot_be_closed_until_reviewed_and_without_open_actions(): void
    {
        $m = $this->manager();
        $svc = app(IncidentService::class);
        $i = $this->incident($this->actor(['quality.incident.report']));
        try {
            $svc->close($i, $m, 'Looked at it and fixed the mat');
            $this->fail('closing before triage must be refused');
        } catch (ValidationException) {
        }
        $i = $svc->triage($i, $m, null, $m);
        app(ActionService::class)->create($this->organization->id, ['source_type' => 'incident', 'source_id' => $i->id, 'action_type' => 'corrective', 'description' => 'Fix the floor mat by the door', 'owner_id' => $m->id], $m);
        try {
            $svc->close($i, $m, 'Looked at it and fixed the mat');
            $this->fail('an open action blocks closing');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('open', implode(' ', $e->errors()['note']));
        }
    }

    public function test_a_closed_incident_can_be_reopened_with_a_reason_and_nothing_can_be_added_while_closed(): void
    {
        $m = $this->manager();
        $svc = app(IncidentService::class);
        $i = $svc->triage($this->incident($this->actor(['quality.incident.report'])), $m, null, $m);
        $i = $svc->close($i, $m, 'Reviewed: no further action needed');
        $this->assertSame('closed', $i->status);
        try {
            $svc->triage($i, $m, 'major', null);
            $this->fail('closed incidents are not triaged');
        } catch (ValidationException) {
        }
        $this->assertSame('triaged', $svc->reopen($i, $m, 'New facts came to light')->status);
    }

    public function test_the_sweep_brings_in_blood_bank_incidents_once(): void
    {
        DB::table('blood_incidents')->insert(['public_id' => (string) Str::ulid(), 'organization_id' => $this->organization->id, 'incident_type' => 'wrong_sample', 'severity' => 'high', 'description' => 'Two samples disagree on the group.', 'status' => 'open', 'occurred_at' => '2026-09-08 09:00:00', 'created_at' => now(), 'updated_at' => now()]);
        $this->assertSame(1, app(IncidentSweep::class)->run($this->organization->id)['blood_incident']);
        $this->assertSame(0, app(IncidentSweep::class)->run($this->organization->id)['blood_incident'], 'a repeat adds nothing');
        $i = QaIncident::query()->where('source_type', 'blood_incident')->first();
        $this->assertSame(['blood', 'major', true], [$i->category, $i->harm, $i->is_anonymous]);
    }
}
