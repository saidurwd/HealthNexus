<?php

namespace Tests\Feature\Quality;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\Quality\QaAction;
use App\Models\Quality\QaCommittee;
use App\Models\Quality\QaIncident;
use App\Models\Quality\QaIncidentReporter;
use App\Models\Quality\QaMortalityReview;
use App\Models\Quality\QaStandard;
use App\Models\User;
use App\Services\Quality\AccreditationService;
use App\Services\Quality\ActionService;
use App\Services\Quality\ClinicalAuditService;
use App\Services\Quality\DocumentService;
use App\Services\Quality\IncidentService;
use App\Services\Quality\IndicatorService;
use App\Services\Quality\InfectionService;
use App\Services\Quality\InvestigationService;
use App\Services\Quality\MeetingService;
use App\Services\TenantContextResolver;

class DeskTest extends QualityTestCase
{
    private function as(User $user): static
    {
        $this->actingAs($user);
        session()->put(['tenant_organization_id' => $this->organization->id, 'tenant_branch_id' => $this->branch->id]);
        app(TenantContextResolver::class)->setOrganizationId($this->organization->id);

        return $this;
    }

    public function test_every_screen_opens_for_the_super_user_and_is_refused_without_rights(): void
    {
        $super = $this->superAdmin();
        $nobody = $this->actor([]);
        $reporter = $this->actor(['quality.incident.report']);
        $incident = $this->incident($reporter, ['harm' => 'major']);
        app(IncidentService::class)->triage($incident, $super, null, $super);
        $inv = app(InvestigationService::class)->start($incident->fresh(), $super, 'five_whys', $super);
        app(ActionService::class)->create($this->organization->id, ['source_type' => 'incident', 'source_id' => $incident->id, 'action_type' => 'corrective', 'description' => 'Fix the door mat', 'owner_id' => $super->id], $super);
        $audit = app(ClinicalAuditService::class)->create($this->organization->id, ['title' => 'Hand hygiene audit'], $super);
        $doc = app(DocumentService::class)->create($this->organization->id, ['code' => 'POL-1', 'title' => 'Policy one', 'category' => 'policy', 'body' => 'Something long enough to be a policy text.'], $super);
        $std = QaStandard::query()->where('code', 'HOSP-BASIC')->first();
        $assessment = app(AccreditationService::class)->start($this->organization->id, $std, 'Annual', $super);
        $meeting = app(MeetingService::class)->schedule(QaCommittee::query()->first(), 'Monthly', '2026-09-20 14:00', null, null);
        $review = QaMortalityReview::create(['organization_id' => $this->organization->id, 'number' => 'MR-D-1', 'patient_id' => $this->patient()->id, 'source_type' => 'ed_visit', 'source_id' => 5, 'died_at' => '2026-09-01 10:00:00', 'status' => 'pending']);
        app(IndicatorService::class)->computeRecent($this->organization->id, 2);
        app(InfectionService::class)->isolate($this->organization->id, $this->patient(), 'contact', 'MRSA', $super);

        $pages = ['', '/incidents', '/incidents/new', '/incidents/mine', '/incidents/'.$incident->public_id, '/actions', '/infection', '/indicators', '/audits', '/audits/'.$audit->public_id, '/mortality', '/mortality/'.$review->public_id,
            '/documents', '/documents/new', '/documents/to-read', '/documents/'.$doc->public_id, '/privileges', '/compliance', '/accreditation', '/accreditation/'.$assessment->public_id, '/committees', '/meetings/'.$meeting->public_id, '/complaints'];
        $this->as($super);
        foreach ($pages as $p) {
            $this->get('/admin/quality'.$p)->assertOk();
        }
        $this->as($nobody);
        foreach (['', '/incidents', '/incidents/new', '/actions', '/infection', '/indicators', '/audits', '/mortality', '/documents', '/privileges', '/compliance', '/accreditation', '/committees', '/complaints'] as $p) {
            $this->get('/admin/quality'.$p)->assertForbidden();
        }
        $this->assertNotNull($inv);
    }

    public function test_any_staff_member_can_report_and_sees_only_their_own_reports(): void
    {
        $nurse = $this->actor(['quality.incident.report']);
        $other = $this->actor(['quality.incident.report']);
        $this->as($nurse)->post('/admin/quality/incidents', ['kind' => 'incident', 'category' => 'medication', 'harm' => 'minor', 'occurred_at' => '2026-09-09T10:30', 'description' => 'Wrong strength of a medicine was drawn up.'])->assertRedirect('/admin/quality/incidents/mine');
        $i = QaIncident::query()->first();
        $this->assertFalse($i->is_anonymous);
        $this->assertSame($this->branch->id, $i->branch_id);
        $this->as($nurse)->get('/admin/quality/incidents/mine')->assertOk()->assertSee($i->number);
        $this->as($nurse)->get('/admin/quality/incidents/'.$i->public_id)->assertOk()->assertDontSee(__('Show who reported this'));
        $this->as($other)->get('/admin/quality/incidents/'.$i->public_id)->assertForbidden();
        $this->as($other)->get('/admin/quality/incidents/mine')->assertOk()->assertDontSee($i->number);
        $this->as($nurse)->get('/admin/quality/incidents')->assertForbidden();
    }

    public function test_an_anonymous_report_cannot_be_traced_through_the_screens(): void
    {
        $nurse = $this->actor(['quality.incident.report']);
        $this->as($nurse)->post('/admin/quality/incidents', ['kind' => 'near_miss', 'category' => 'fall', 'harm' => 'none', 'occurred_at' => '2026-09-09T10:30', 'description' => 'A wet floor was noticed before anyone slipped.', 'anonymous' => '1'])->assertRedirect();
        $i = QaIncident::query()->first();
        $this->assertTrue($i->is_anonymous);
        $this->assertSame(0, QaIncidentReporter::query()->count());
        $this->as($nurse)->get('/admin/quality'); // the confirmation message is shown once
        $this->as($nurse)->get('/admin/quality/incidents/mine')->assertOk()->assertDontSee($i->number);
        $this->as($nurse)->get('/admin/quality/incidents/'.$i->public_id)->assertForbidden();
        $boss = $this->actor(['quality.incident.view', 'quality.incident.reveal']);
        $this->as($boss)->post('/admin/quality/incidents/'.$i->public_id.'/reveal', ['reason' => 'We need to follow this up properly'])->assertSessionHas('warning');
    }

    public function test_the_reporter_is_shown_only_to_those_with_the_reveal_right_and_a_reason(): void
    {
        $nurse = $this->actor(['quality.incident.report']);
        $i = $this->incident($nurse);
        $viewer = $this->actor(['quality.incident.view', 'quality.incident.triage']);
        $this->as($viewer)->post('/admin/quality/incidents/'.$i->public_id.'/reveal', ['reason' => 'Need to ask a question'])->assertForbidden();
        $revealer = $this->actor(['quality.incident.view', 'quality.incident.reveal']);
        $this->as($revealer)->post('/admin/quality/incidents/'.$i->public_id.'/reveal', ['reason' => 'short'])->assertSessionHasErrors('reason');
        $this->as($revealer)->post('/admin/quality/incidents/'.$i->public_id.'/reveal', ['reason' => 'We need to ask what the floor looked like'])->assertSessionHas('success', 'Reported by '.$nurse->name.'. This was recorded.');
        $this->as($viewer)->get('/admin/quality'); // the earlier message is shown once
        $html = $this->as($viewer)->get('/admin/quality/incidents/'.$i->public_id)->assertOk()->getContent();
        $timeline = substr($html, (int) strpos($html, 'What has happened since'));
        $timeline = substr($timeline, 0, (int) strpos($timeline, '</ul>'));
        $this->assertStringNotContainsString($nurse->name, $timeline, 'the timeline never names the reporter');
        $this->assertStringNotContainsString('Reported by', $html);
    }

    public function test_a_branch_limited_user_only_sees_their_branches_incidents(): void
    {
        $other = Branch::factory()->create(['organization_id' => $this->organization->id]);
        $here = $this->incident(null, ['branch_id' => $this->branch->id]);
        $there = $this->incident(null, ['branch_id' => $other->id]);
        $limited = $this->actor(['quality.incident.view']);
        $this->as($limited)->get('/admin/quality/incidents')->assertOk()->assertSee($here->number)->assertDontSee($there->number);
        $this->as($limited)->get('/admin/quality/incidents/'.$there->public_id)->assertForbidden();
        $manager = $this->manager();
        $this->as($manager)->get('/admin/quality/incidents')->assertOk()->assertSee($here->number)->assertSee($there->number);
    }

    public function test_another_hospitals_records_do_not_exist(): void
    {
        $i = $this->incident();
        $other = Organization::factory()->create();
        $user = $this->actor(['quality.incident.view', 'quality.incident.triage', 'quality.dashboard.view']);
        $user->organizations()->sync([$other->id => ['access_level' => 'admin']]);
        session()->put(['tenant_organization_id' => $other->id]);
        $this->actingAs($user);
        app(TenantContextResolver::class)->setOrganizationId($other->id);
        $this->get('/admin/quality/incidents/'.$i->public_id)->assertNotFound();
    }

    public function test_the_action_screen_lets_an_owner_finish_their_own_action_but_not_someone_elses(): void
    {
        $m = $this->manager();
        $owner = $this->actor(['quality.incident.report']);
        $stranger = $this->actor(['quality.incident.report']);
        $a = app(ActionService::class)->create($this->organization->id, ['action_type' => 'improvement', 'description' => 'Put up the new hand hygiene posters', 'owner_id' => $owner->id], $m);
        $this->as($stranger)->post('/admin/quality/actions/'.$a->public_id.'/complete', ['note' => 'I did it for them'])->assertSessionHasErrors('action');
        $this->as($owner)->post('/admin/quality/actions/'.$a->public_id.'/complete', ['note' => 'Posters are up in all wards'])->assertSessionHas('success');
        $this->assertSame('done', QaAction::find($a->id)->status);
        $this->as($owner)->post('/admin/quality/actions/'.$a->public_id.'/verify', ['effective' => 'yes'])->assertForbidden();
        $this->as($m)->post('/admin/quality/actions/'.$a->public_id.'/verify', ['effective' => 'yes', 'note' => 'Seen'])->assertSessionHas('success');
        $this->as($owner)->get('/admin/quality/actions')->assertOk()->assertSee($a->number);
    }

    public function test_the_documents_screen_asks_staff_to_read_and_records_it(): void
    {
        $author = $this->actor(['quality.document.manage', 'quality.document.view']);
        $approver = $this->actor(['quality.document.approve', 'quality.document.view']);
        $nurse = $this->actor(['quality.document.view', 'quality.document.acknowledge']);
        $svc = app(DocumentService::class);
        $doc = $svc->create($this->organization->id, ['code' => 'SOP-9', 'title' => 'Safe injection practice', 'category' => 'sop', 'body' => 'Use a new needle and syringe for every injection.'], $author);
        $svc->assign($doc, 'all');
        $svc->submit($doc->versions()->first(), $author);
        $this->as($approver)->post('/admin/quality/document-versions/'.$doc->versions()->first()->id.'/decide', ['decision' => 'approve'])->assertSessionHas('success');
        $this->as($nurse)->get('/admin/quality/documents/to-read')->assertOk()->assertSee('SOP-9');
        $this->as($nurse)->get('/admin/quality/documents/'.$doc->public_id)->assertOk()->assertSee('Use a new needle');
        $this->as($nurse)->post('/admin/quality/documents/'.$doc->public_id.'/acknowledge')->assertSessionHas('success');
        $this->as($nurse)->get('/admin/quality/documents/to-read')->assertOk()->assertDontSee('SOP-9');
        $this->as($nurse)->post('/admin/quality/documents/'.$doc->public_id.'/version', ['body' => 'x', 'change_note' => 'y'])->assertForbidden();
    }

    public function test_the_read_only_api_needs_a_login_the_right_and_the_header(): void
    {
        $this->incident($this->actor(['quality.incident.report']), ['harm' => 'major']);
        app(IndicatorService::class)->computeRecent($this->organization->id, 1);
        foreach (['overview', 'indicators', 'incidents', 'actions'] as $p) {
            $this->getJson("/api/v1/quality/$p")->assertUnauthorized();
        }
        $user = $this->manager();
        $h = ['Authorization' => 'Bearer '.$user->createToken('t')->plainTextToken, 'X-Organization-Id' => (string) $this->organization->id];
        $r = $this->getJson('/api/v1/quality/overview', $h)->assertOk()->assertJsonPath('success', true);
        $this->assertSame(1, $r->json('data.incidents_open'));
        $this->getJson('/api/v1/quality/indicators', $h)->assertForbidden();
        $list = $this->getJson('/api/v1/quality/incidents', $h)->assertOk();
        $this->assertSame(['major', false], [$list->json('data.0.harm'), $list->json('data.0.anonymous')]);
        $this->assertArrayNotHasKey('reporter', $list->json('data.0'));
        $this->assertStringNotContainsString('user_id', json_encode($list->json()));
        $this->getJson('/api/v1/quality/actions', $h)->assertOk();
        $other = Organization::factory()->create();
        $this->getJson('/api/v1/quality/overview', ['X-Organization-Id' => (string) $other->id] + $h)->assertForbidden();
        $this->getJson('/api/v1/quality/overview', ['Authorization' => $h['Authorization']])->assertStatus(422);
    }
}
