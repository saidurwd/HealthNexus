<?php

namespace Tests\Feature\Quality;

use App\Models\Hr\HrEmployee;
use App\Models\Quality\QaAcknowledgement;
use App\Models\Quality\QaAction;
use App\Models\Quality\QaCommittee;
use App\Models\Quality\QaDocument;
use App\Models\Quality\QaMortalityReview;
use App\Models\Quality\QaPrivilegeCatalog;
use App\Models\Quality\QaStandard;
use App\Models\User;
use App\Services\Crm\ComplaintService;
use App\Services\Quality\AccreditationService;
use App\Services\Quality\ComplaintLinkService;
use App\Services\Quality\ComplianceService;
use App\Services\Quality\DocumentService;
use App\Services\Quality\MeetingService;
use App\Services\Quality\PrivilegeService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GovernanceTest extends QualityTestCase
{
    // ---- document control -------------------------------------------------------------------------------------------------------------------------------------

    private function doc(User $author, string $code = 'IPC-01'): QaDocument
    {
        return app(DocumentService::class)->create($this->organization->id, ['code' => $code, 'title' => 'Hand hygiene policy', 'category' => 'policy', 'body' => 'Clean your hands before and after every patient contact.', 'review_months' => 12], $author);
    }

    public function test_a_version_is_approved_by_someone_other_than_its_author_and_then_never_edited(): void
    {
        $author = $this->actor(['quality.document.manage']);
        $approver = $this->actor(['quality.document.approve']);
        $svc = app(DocumentService::class);
        $doc = $this->doc($author);
        $v1 = $doc->versions()->first();
        $svc->submit($v1, $author);
        try {
            $svc->decide($v1->fresh(), $author, true, null);
            $this->fail('the author cannot approve');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('other than the author', implode(' ', $e->errors()['status']));
        }
        $svc->decide($v1->fresh(), $approver, true, null, '2026-09-10');
        $v1 = $v1->fresh();
        $this->assertSame(['approved', '2026-09-10', '2027-09-10'], [$v1->status, $v1->effective_on->toDateString(), $v1->review_due_on->toDateString()]);
        $this->assertSame($v1->id, $doc->fresh()->current_version_id);
        try {
            $v1->update(['body' => 'Quietly changed']);
            $this->fail('the model should refuse');
        } catch (\LogicException) {
        }
        try {
            DB::table('qa_document_versions')->where('id', $v1->id)->update(['body' => 'Changed by SQL']);
            $this->fail('SQL should be refused');
        } catch (\Throwable $e) {
            $this->assertStringContainsString('cannot be changed', $e->getMessage());
        }
        $this->expectException(\Throwable::class);
        DB::table('qa_document_versions')->where('id', $v1->id)->delete();
    }

    public function test_a_new_version_supersedes_the_old_one_and_asks_everyone_again(): void
    {
        $author = $this->actor(['quality.document.manage']);
        $approver = $this->actor(['quality.document.approve']);
        $nurse = $this->actor(['quality.document.acknowledge']);
        $svc = app(DocumentService::class);
        $doc = $this->doc($author);
        $svc->assign($doc, 'all');
        $v1 = $doc->versions()->first();
        $svc->submit($v1, $author);
        $svc->decide($v1->fresh(), $approver, true, null);
        $this->assertTrue($svc->pendingFor($nurse, $this->organization->id)->contains('id', $doc->id));
        $svc->acknowledge($doc->fresh(), $nurse);
        $svc->acknowledge($doc->fresh(), $nurse); // idempotent
        $this->assertSame(1, QaAcknowledgement::query()->where('user_id', $nurse->id)->count());
        $this->assertFalse($svc->pendingFor($nurse, $this->organization->id)->contains('id', $doc->id));

        $v2 = $svc->newVersion($doc->fresh(), 'Clean your hands at the five moments listed on the poster.', 'Added the five moments', $author);
        try {
            $svc->newVersion($doc->fresh(), 'Another draft at the same time.', 'Parallel work', $author);
            $this->fail('one draft at a time');
        } catch (ValidationException) {
        }
        $svc->submit($v2, $author);
        $svc->decide($v2->fresh(), $approver, true, 'Looks right');
        $this->assertSame('superseded', $v1->fresh()->status);
        $this->assertSame($v2->id, $doc->fresh()->current_version_id);
        $this->assertTrue($svc->pendingFor($nurse, $this->organization->id)->contains('id', $doc->id), 'the new version must be read');
        $ack = $svc->acknowledgement($doc->fresh());
        $this->assertSame(0, $ack['acknowledged'] > 0 && $ack['acknowledged'] === $ack['required'] ? 1 : 0);
    }

    public function test_a_rejected_draft_goes_back_to_its_author_and_audiences_are_checked(): void
    {
        $author = $this->actor(['quality.document.manage']);
        $approver = $this->actor(['quality.document.approve']);
        $svc = app(DocumentService::class);
        $doc = $this->doc($author, 'IPC-02');
        $v = $doc->versions()->first();
        $svc->submit($v, $author);
        $svc->decide($v->fresh(), $approver, false, 'The scope is missing');
        $this->assertSame('rejected', $v->fresh()->status);
        $svc->edit($v->fresh(), 'Scope: all wards. Clean your hands before and after every patient contact.', 'Added scope');
        $this->assertSame('draft', $v->fresh()->status);
        foreach ([['role', 'no_such_role'], ['department', '99999'], ['user', '99999'], ['everyone', '']] as [$aud, $val]) {
            try {
                $svc->assign($doc, $aud, $val);
                $this->fail("accepted $aud");
            } catch (ValidationException) {
            }
        }
        $this->assertTrue(true);
    }

    public function test_the_document_code_is_unique_and_tidy(): void
    {
        $author = $this->actor(['quality.document.manage']);
        $this->doc($author);
        foreach (['IPC-01', 'ipc 01', ''] as $code) {
            try {
                $this->doc($author, $code);
                $this->fail("accepted '$code'");
            } catch (ValidationException) {
            }
        }
        $this->assertTrue(true);
    }

    // ---- privileges and the compliance calendar ---------------------------------------------------------------------------------------------------------------

    private function employee(?User $user = null): HrEmployee
    {
        return HrEmployee::factory()->create(['organization_id' => $this->organization->id, 'user_id' => $user?->id, 'status' => 'active']);
    }

    private function licence(HrEmployee $e, string $type = 'bmdc', string $expires = '2028-01-01'): void
    {
        DB::table('hr_credentials')->insert(['organization_id' => $this->organization->id, 'employee_id' => $e->id, 'credential_type' => $type, 'number' => 'A-1', 'expires_on' => $expires, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_a_privilege_needs_a_valid_licence_and_a_second_person(): void
    {
        $svc = app(PrivilegeService::class);
        $requester = $this->actor(['quality.privilege.request']);
        $decider = $this->actor(['quality.privilege.decide']);
        $surgeon = $this->employee();
        $item = QaPrivilegeCatalog::query()->where('code', 'SURG-GEN')->first();
        $p = $svc->request($this->organization->id, $surgeon, $item, $requester);
        try {
            $svc->request($this->organization->id, $surgeon, $item, $requester);
            $this->fail('already requested');
        } catch (ValidationException) {
        }
        try {
            $svc->decide($p, $requester, true, null);
            $this->fail('the requester cannot grant');
        } catch (ValidationException) {
        }
        try {
            $svc->decide($p->fresh(), $decider, true, null);
            $this->fail('no licence on file');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('BMDC', implode(' ', $e->errors()['status']));
        }
        $this->licence($surgeon, 'bmdc', '2027-03-01');
        $granted = $svc->decide($p->fresh(), $decider, true, 'Case log reviewed');
        $this->assertSame('granted', $granted->status);
        $this->assertSame('2027-03-01', $granted->expires_on->toDateString(), 'never beyond the licence');
        $this->assertTrue($svc->holds($this->organization->id, $surgeon->id, 'surg-gen'));
        $this->assertFalse($svc->holds($this->organization->id, $surgeon->id, 'ANAES'));
    }

    public function test_nobody_decides_their_own_privileges(): void
    {
        $svc = app(PrivilegeService::class);
        $doctor = $this->actor(['quality.privilege.decide']);
        $employee = $this->employee($doctor);
        $this->licence($employee);
        $p = $svc->request($this->organization->id, $employee, QaPrivilegeCatalog::query()->where('code', 'SURG-GEN')->first(), $this->actor(['quality.privilege.request']));
        $this->expectException(ValidationException::class);
        $svc->decide($p, $doctor, true, null);
    }

    public function test_privileges_lapse_with_the_licence_and_with_time(): void
    {
        $svc = app(PrivilegeService::class);
        $e1 = $this->employee();
        $e2 = $this->employee();
        $this->licence($e1);
        $this->licence($e2);
        $item = QaPrivilegeCatalog::query()->where('code', 'SURG-GEN')->first();
        $ids = [];
        foreach ([$e1, $e2] as $e) {
            $p = $svc->request($this->organization->id, $e, $item, $this->actor(['quality.privilege.request']));
            $ids[] = $svc->decide($p, $this->actor(['quality.privilege.decide']), true, null)->id;
        }
        DB::table('hr_credentials')->where('employee_id', $e2->id)->update(['status' => 'revoked']);
        Carbon::setTestNow('2028-06-01 10:00:00');
        $r = $svc->maintain($this->organization->id);
        $this->assertSame(['expired' => 2, 'suspended' => 0], ['expired' => $r['expired'], 'suspended' => $r['suspended']]);
    }

    public function test_the_compliance_calendar_moves_to_the_next_date_and_warns_once_a_week(): void
    {
        $svc = app(ComplianceService::class);
        $owner = $this->actor(['quality.compliance.manage']);
        $item = $svc->add($this->organization->id, ['kind' => 'licence', 'title' => 'Hospital operating licence', 'authority' => 'Directorate', 'due_on' => '2026-10-01', 'interval_months' => 12, 'owner_id' => $owner->id], $owner);
        $this->assertSame('due_soon', $svc->standing($item));
        $this->assertSame(1, $svc->alertDue($this->organization->id));
        $this->assertSame(0, $svc->alertDue($this->organization->id), 'once a week');
        $svc->complete($item->fresh(), $owner, '2026-09-28');
        $item = $item->fresh();
        $this->assertSame('2027-09-28', $item->due_on->toDateString());
        $this->assertSame('current', $svc->standing($item));
        Carbon::setTestNow('2028-01-01 10:00:00');
        $this->assertSame('overdue', $svc->standing($item->fresh()));
        $this->expectException(ValidationException::class);
        $svc->add($this->organization->id, ['kind' => 'lunch', 'title' => 'Not a kind', 'due_on' => '2026-10-01'], $owner);
    }

    // ---- accreditation checklists -----------------------------------------------------------------------------------------------------------------------------

    public function test_an_assessment_cannot_finish_with_unassessed_items_or_gaps_without_an_action(): void
    {
        $svc = app(AccreditationService::class);
        $lead = $this->actor(['quality.accreditation.manage', 'quality.action.manage']);
        $std = $svc->addStandard($this->organization->id, 'MINI', 'Mini checklist', null, [['code' => 'A', 'requirement' => 'Patients are identified with two identifiers.'], ['code' => 'B', 'requirement' => 'Hand-hygiene supplies are at every bed.'], ['code' => 'C', 'requirement' => 'Not relevant to us.']]);
        $a = $svc->start($this->organization->id, $std, '', $lead);
        $r = $a->results()->with('item')->get()->keyBy(fn ($x) => $x->item->code);
        try {
            $svc->complete($a, $lead);
            $this->fail('items unassessed');
        } catch (ValidationException) {
        }
        try {
            $svc->record($a, $r['A'], $lead, 'compliant', null);
            $this->fail('evidence is needed');
        } catch (ValidationException) {
        }
        $svc->record($a, $r['A'], $lead, 'compliant', 'Seen on 10 wristbands');
        $svc->record($a, $r['B'], $lead, 'non_compliant', 'No gel at beds in ward 3');
        $svc->record($a, $r['C'], $lead, 'na', null);
        try {
            $svc->complete($a->fresh(), $lead);
            $this->fail('a gap needs an action');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('no action', implode(' ', $e->errors()['status']));
        }
        $action = $svc->gapAction($a->fresh(), $r['B']->fresh(), ['owner_id' => $lead->id, 'due_on' => '2026-10-15'], $lead);
        $this->assertSame('assessment', $action->source_type);
        $done = $svc->complete($a->fresh(), $lead);
        $this->assertSame(50.0, (float) $done->score, 'one of the two applicable items is compliant; not applicable is left out');
    }

    public function test_the_starter_checklist_is_present_and_generic(): void
    {
        $std = QaStandard::query()->where('code', 'HOSP-BASIC')->first();
        $this->assertNotNull($std);
        $this->assertGreaterThanOrEqual(15, $std->items()->count());
        $this->assertStringContainsString('example', strtolower($std->name));
    }

    // ---- committees and meetings ----------------------------------------------------------------------------------------------------------------------------

    public function test_a_meeting_goes_from_agenda_to_minutes_and_each_case_leaves_an_action(): void
    {
        $chair = $this->actor(['quality.committee.manage']);
        $svc = app(MeetingService::class);
        $mm = QaCommittee::query()->where('kind', 'mm')->first();
        $review = QaMortalityReview::create(['organization_id' => $this->organization->id, 'number' => 'MR-T-1', 'patient_id' => $this->patient()->id, 'source_type' => 'ed_visit', 'source_id' => 1, 'died_at' => '2026-08-20 03:00:00', 'status' => 'reviewed', 'classification' => 'potentially_preventable', 'reviewed_at' => now()]);
        $m = $svc->schedule($mm, 'August M&M', '2026-09-15 14:00', 'Cases from August', 'Seminar room');
        $this->assertSame('2026-09-15 08:00:00', $m->scheduled_at->toDateTimeString(), 'stored in UTC');
        $item = $svc->addItem($m, 'mortality', $review->id);
        $topic = $svc->addItem($m, 'topic', null, 'Sepsis bundle timing');
        try {
            $svc->addItem($m, 'mortality', $review->id);
            $this->fail('twice on the agenda');
        } catch (ValidationException) {
        }
        try {
            $svc->minute($m->fresh(), $chair, 'Minutes written before the meeting took place.');
            $this->fail('hold first');
        } catch (ValidationException) {
        }
        $svc->hold($m->fresh(), [$chair->id, 999999], $chair);
        $this->assertSame([$chair->id], $m->fresh()->attendees, 'only people of this hospital');
        $svc->recordItem($m->fresh(), $item, $chair, 'Delay to senior review discussed at length.', 'Escalation rule to be changed', ['description' => 'Write a rule: senior review within 15 minutes for chest pain', 'owner_id' => $chair->id, 'due_on' => '2026-10-20']);
        $this->assertSame('discussed', $review->fresh()->status);
        try {
            $svc->minute($m->fresh(), $chair, 'Minutes of the meeting with one case still open.');
            $this->fail('an item has no outcome');
        } catch (ValidationException) {
        }
        $svc->recordItem($m->fresh(), $topic, $chair, null, 'Noted; revisit next quarter');
        $svc->minute($m->fresh(), $chair, 'Present: chair. Two items discussed; one action given to the chair.');
        $this->assertSame('minuted', $m->fresh()->status);
        $this->assertSame(1, QaAction::query()->where(['source_type' => 'meeting', 'source_id' => $m->id])->count());
        $this->expectException(ValidationException::class);
        $svc->addItem($m->fresh(), 'topic', null, 'Too late to add');
    }

    // ---- complaints -----------------------------------------------------------------------------------------------------------------------------------------------------

    public function test_a_complaint_can_be_raised_as_one_incident_and_only_its_number_travels(): void
    {
        $patient = $this->patient();
        $complaint = app(ComplaintService::class)->lodge($this->organization->id, ['category' => 'clinical_care', 'description' => 'My father was left on a trolley for hours and nobody told us anything about the delay.', 'severity' => 'high'], null, $patient);
        $by = $this->actor(['quality.complaint.link', 'quality.incident.report']);
        $svc = app(ComplaintLinkService::class);
        $incident = $svc->raise($complaint, $by);
        $this->assertSame('procedure', $incident->category);
        $this->assertStringContainsString($complaint->number, $incident->description);
        $this->assertStringNotContainsString('trolley', $incident->description, 'the complaint text stays in patient relations');
        $this->assertSame([$complaint->number], $svc->numbers($incident));
        $this->assertSame($patient->id, $incident->patient_id);
        $this->expectException(ValidationException::class);
        $svc->raise($complaint, $by);
    }
}
