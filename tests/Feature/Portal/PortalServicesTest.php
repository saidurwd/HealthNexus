<?php

namespace Tests\Feature\Portal;

use App\Models\ClinicalOrder;
use App\Models\Portal\PortalAccessLog;
use App\Models\Portal\PortalDocument;
use App\Models\Portal\PortalNotification;
use App\Models\Portal\PortalProxy;
use App\Models\Portal\PortalRefillRequest;
use App\Models\Portal\PortalResultRelease;
use App\Models\Portal\PortalServiceRequest;
use App\Models\Portal\PortalThread;
use App\Models\Prescription;
use App\Services\Portal\DocumentShareService;
use App\Services\Portal\MessagingService;
use App\Services\Portal\PortalAccess;
use App\Services\Portal\PortalException;
use App\Services\Portal\ProxyService;
use App\Services\Portal\RefillService;
use App\Services\Portal\ResultReleaseService;
use App\Services\Portal\ServiceRequestService;

class PortalServicesTest extends PortalTestCase
{
    // ---- messages ---------------------------------------------------------------------------------------------------------------------------------------

    public function test_a_message_that_sounds_like_an_emergency_is_flagged_and_the_patient_is_told_to_call(): void
    {
        $patient = $this->patient();
        $headers = $this->bearer($this->account($patient));

        $res = $this->postJson('/api/v1/portal/threads', ['subject' => 'Help', 'body' => 'I have severe chest pain since morning'], $headers)->assertCreated();
        $this->assertTrue($res->json('meta.urgent'));
        $this->assertStringContainsString('999', $res->json('message'));
        $thread = PortalThread::query()->first();
        $this->assertTrue($thread->urgent);
        $this->assertTrue($thread->reply_due_at->lt(now()->addHours(3)));
        $this->assertSame(['patient', 'system'], $thread->messages()->orderBy('id')->pluck('sender')->all());
    }

    public function test_an_ordinary_message_is_not_urgent_and_a_message_creates_no_clinical_order(): void
    {
        $patient = $this->patient();
        $headers = $this->bearer($this->account($patient));
        $res = $this->postJson('/api/v1/portal/threads', ['subject' => 'Refill', 'category' => 'prescription', 'body' => 'Please order a blood test and refill my tablets'], $headers)->assertCreated();

        $this->assertFalse($res->json('meta.urgent'));
        $this->assertSame(0, ClinicalOrder::query()->count());
        $this->assertSame(0, Prescription::query()->count());
    }

    public function test_staff_reply_notifies_the_patient_and_the_patient_can_answer_until_it_is_closed(): void
    {
        $patient = $this->patient();
        $account = $this->account($patient);
        $headers = $this->bearer($account);
        $id = $this->postJson('/api/v1/portal/threads', ['subject' => 'Visit time', 'body' => 'Can I come at 3?'], $headers)->json('data.id');
        $thread = PortalThread::query()->where('public_id', $id)->first();
        $staff = $this->actor(['portal.message.reply']);

        app(MessagingService::class)->staffReply($thread, $staff, 'Yes, 3 pm is fine.');
        $this->assertSame('answered', $thread->fresh()->status);
        $this->assertSame(1, PortalNotification::query()->where(['patient_id' => $patient->id, 'kind' => 'message'])->count());

        $this->postJson("/api/v1/portal/threads/$id/reply", ['body' => 'Thank you'], $headers)->assertCreated();
        $this->assertSame('open', $thread->fresh()->status);

        app(MessagingService::class)->close($thread);
        $this->postJson("/api/v1/portal/threads/$id/reply", ['body' => 'More'], $headers)->assertStatus(409);
    }

    public function test_html_in_a_message_is_removed_and_nobody_reads_another_patients_thread(): void
    {
        $patient = $this->patient();
        $headers = $this->bearer($this->account($patient));
        $id = $this->postJson('/api/v1/portal/threads', ['subject' => '<b>Hi</b>', 'body' => 'Hello <script>alert(1)</script>there'], $headers)->json('data.id');
        $this->assertStringNotContainsString('<script>', PortalThread::query()->first()->messages()->first()->body);

        $this->getJson("/api/v1/portal/threads/$id", $this->bearer($this->account($this->patient())))->assertNotFound();
    }

    // ---- refills ----------------------------------------------------------------------------------------------------------------------------------------

    public function test_a_refill_request_goes_to_staff_and_is_not_repeated(): void
    {
        $patient = $this->patient();
        $headers = $this->bearer($this->account($patient));
        $rx = $this->prescription($patient);

        $this->postJson("/api/v1/portal/prescriptions/{$rx->public_id}/refill", ['note' => 'Running low'], $headers + ['Idempotency-Key' => 'r1'])->assertCreated()->assertJsonPath('data.status', 'requested');
        $this->postJson("/api/v1/portal/prescriptions/{$rx->public_id}/refill", ['note' => 'Running low'], $headers + ['Idempotency-Key' => 'r1'])->assertCreated();
        $this->postJson("/api/v1/portal/prescriptions/{$rx->public_id}/refill", [], $headers + ['Idempotency-Key' => 'r2'])->assertStatus(409);
        $this->assertSame(1, PortalRefillRequest::query()->count());
        $this->assertSame(1, Prescription::query()->count(), 'a request issues nothing');
    }

    public function test_an_old_prescription_and_someone_elses_cannot_be_refilled(): void
    {
        $patient = $this->patient();
        $headers = $this->bearer($this->account($patient));
        $old = $this->prescription($patient, '-120 days');
        $theirs = $this->prescription($this->patient());

        $this->postJson("/api/v1/portal/prescriptions/{$old->public_id}/refill", [], $headers + ['Idempotency-Key' => 'a'])->assertStatus(409);
        $this->postJson("/api/v1/portal/prescriptions/{$theirs->public_id}/refill", [], $headers + ['Idempotency-Key' => 'b'])->assertNotFound();
    }

    public function test_staff_decide_a_refill_once_and_the_patient_is_told(): void
    {
        $patient = $this->patient();
        $account = $this->account($patient);
        $rx = $this->prescription($patient);
        $row = app(RefillService::class)->request($account, $patient, $rx, null, 'k');
        $staff = $this->actor(['portal.refill.review']);

        app(RefillService::class)->decide($row, $staff, 'needs_visit', 'Please book a review');
        $this->assertSame('needs_visit', $row->fresh()->status);
        $this->assertSame(1, PortalNotification::query()->where(['patient_id' => $patient->id, 'kind' => 'prescription'])->count());
        $this->expectException(PortalException::class);
        app(RefillService::class)->decide($row->fresh(), $staff, 'approved', null);
    }

    // ---- family / proxy ---------------------------------------------------------------------------------------------------------------------------------

    private function child(int $years = 8)
    {
        return $this->patient(['date_of_birth' => now()->subYears($years)->toDateString(), 'enterprise_patient_no' => 'EPT-CHILD'.$years]);
    }

    public function test_a_parent_can_act_for_a_child_only_after_staff_approve_and_only_for_the_approved_parts(): void
    {
        $parent = $this->account($this->patient());
        $child = $this->child(8);
        $headers = $this->bearer($parent);
        $staff = $this->actor(['portal.proxy.review']);

        $this->postJson('/api/v1/portal/family/requests', ['identifier' => $child->enterprise_patient_no, 'date_of_birth' => $child->date_of_birth->toDateString(), 'relationship' => 'parent', 'scopes' => ['appointments', 'billing', 'results']], $headers)->assertStatus(202);
        $proxy = PortalProxy::query()->first();
        $this->assertSame('requested', $proxy->status);
        $this->getJson("/api/v1/portal/appointments?patient={$child->public_id}", $headers)->assertForbidden();

        app(ProxyService::class)->approve($proxy, $staff, ['appointments', 'billing']);
        app('auth')->forgetGuards();
        $this->getJson("/api/v1/portal/appointments?patient={$child->public_id}", $headers)->assertOk();
        $this->getJson("/api/v1/portal/results?patient={$child->public_id}", $headers)->assertForbidden();
        $this->getJson("/api/v1/portal/prescriptions?patient={$child->public_id}", $headers)->assertForbidden();
        $this->assertSame(1, PortalNotification::query()->where('patient_id', $child->id)->count());
    }

    public function test_a_wrong_date_of_birth_gets_the_same_answer_but_records_nothing(): void
    {
        $parent = $this->account($this->patient());
        $child = $this->child(8);
        $headers = $this->bearer($parent);

        $this->postJson('/api/v1/portal/family/requests', ['identifier' => $child->enterprise_patient_no, 'date_of_birth' => '2000-01-01', 'relationship' => 'parent', 'scopes' => ['billing']], $headers)->assertStatus(202);
        $this->postJson('/api/v1/portal/family/requests', ['identifier' => 'EPT-NOBODY', 'date_of_birth' => '2000-01-01', 'relationship' => 'parent', 'scopes' => ['billing']], $headers)->assertStatus(202);
        $this->assertSame(0, PortalProxy::query()->count());
    }

    public function test_a_teenagers_results_and_messages_stay_private_from_a_parent(): void
    {
        $parent = $this->account($this->patient());
        $teen = $this->child(15);
        $proxy = PortalProxy::create(['organization_id' => $this->organization->id, 'account_id' => $parent->id, 'patient_id' => $teen->id, 'relationship' => 'parent', 'scopes' => ['appointments', 'results', 'messages', 'billing'], 'status' => 'active', 'valid_from' => now()->toDateString(), 'valid_to' => now()->addYear()->toDateString()]);
        $subject = app(PortalAccess::class)->subjects($parent->fresh())->firstWhere(fn ($s) => $s['relation'] === 'parent');

        $this->assertEqualsCanonicalizing(['appointments', 'billing'], $subject['scopes']);
        $this->assertSame('active', $proxy->fresh()->status);
    }

    public function test_a_parents_approval_ends_when_the_child_turns_eighteen(): void
    {
        $parent = $this->account($this->patient());
        $child = $this->child(17);
        app(ProxyService::class)->request($parent, $child->enterprise_patient_no, $child->date_of_birth->toDateString(), 'parent', ['billing'], null);
        $approved = app(ProxyService::class)->approve(PortalProxy::query()->first(), $this->actor(['portal.proxy.review']), null, now()->addYears(3)->toDateString());

        $this->assertSame($child->date_of_birth->copy()->addYears(18)->toDateString(), $approved->valid_to->toDateString());
    }

    public function test_an_expired_or_revoked_approval_stops_working_and_the_person_can_end_it_themselves(): void
    {
        $parent = $this->account($this->patient());
        $child = $this->child(8);
        $svc = app(ProxyService::class);
        $svc->request($parent, $child->enterprise_patient_no, $child->date_of_birth->toDateString(), 'parent', ['billing'], null);
        $proxy = $svc->approve(PortalProxy::query()->first(), $this->actor(['portal.proxy.review']));
        $this->assertCount(2, app(PortalAccess::class)->subjects($parent->fresh()));

        $this->travel(13)->months();
        $this->assertCount(1, app(PortalAccess::class)->subjects($parent->fresh()), 'out of date');
        $this->assertSame(1, $svc->expireDue());

        $this->travel(-13)->months();
        $proxy->update(['status' => 'active', 'valid_to' => now()->addMonth()->toDateString()]);
        $svc->revoke($proxy, 'ended');
        $this->assertCount(1, app(PortalAccess::class)->subjects($parent->fresh()));
    }

    public function test_reading_through_a_proxy_is_logged_as_proxy_access(): void
    {
        $this->travel(0)->days();
        $parent = $this->account($this->patient());
        $child = $this->child(8);
        $proxy = PortalProxy::create(['organization_id' => $this->organization->id, 'account_id' => $parent->id, 'patient_id' => $child->id, 'relationship' => 'parent', 'scopes' => ['results'], 'status' => 'active', 'valid_from' => now()->toDateString(), 'valid_to' => now()->addYear()->toDateString()]);
        $this->labReport($child);
        app(ResultReleaseService::class)->sync();
        $row = PortalResultRelease::query()->first();
        app(ResultReleaseService::class)->release($row, $this->actor(['portal.release.manage']));

        $this->getJson("/api/v1/portal/results/{$row->public_id}?patient={$child->public_id}", $this->bearer($parent))->assertOk();
        $log = PortalAccessLog::query()->where('action', 'view_result')->first();
        $this->assertTrue($log->as_proxy);
        $this->assertSame($child->id, $log->patient_id);
        $this->assertSame($proxy->account_id, $log->account_id);
    }

    // ---- documents --------------------------------------------------------------------------------------------------------------------------------------

    private function releasedReport($patient): PortalResultRelease
    {
        $this->labReport($patient);
        app(ResultReleaseService::class)->sync();
        $row = PortalResultRelease::query()->latest('id')->first();
        app(ResultReleaseService::class)->release($row, $this->actor(['portal.release.manage']));

        return $row;
    }

    public function test_a_shared_link_opens_for_the_owner_is_counted_and_logged(): void
    {
        $patient = $this->patient();
        $account = $this->account($patient);
        $row = $this->releasedReport($patient);
        $headers = $this->bearer($account);

        $res = $this->postJson('/api/v1/portal/documents/share', ['type' => 'lab_report', 'id' => $row->public_id], $headers)->assertCreated();
        $token = basename($res->json('data.url'));
        $this->getJson("/api/v1/portal/documents/$token", $headers)->assertOk()->assertJsonPath('data.type', 'lab_report');

        $this->assertSame(1, PortalDocument::query()->first()->downloads);
        $this->assertSame(1, PortalAccessLog::query()->where('action', 'download_document')->count());
        $this->assertStringNotContainsString($token, (string) PortalDocument::query()->first()->token_hash, 'only a hash is kept');
    }

    public function test_a_link_expires_runs_out_of_downloads_and_can_be_revoked(): void
    {
        $patient = $this->patient();
        $account = $this->account($patient);
        $row = $this->releasedReport($patient);
        $headers = $this->bearer($account);
        $svc = app(DocumentShareService::class);

        [$doc, $plain] = $svc->share($patient->id, 'lab_report', $row->source_id, $account);
        $doc->update(['max_downloads' => 1]);
        $this->getJson("/api/v1/portal/documents/$plain", $headers)->assertOk();
        $this->getJson("/api/v1/portal/documents/$plain", $headers)->assertStatus(410);

        [, $fresh] = $svc->share($patient->id, 'lab_report', $row->source_id, $account);
        $this->travel(8)->days();
        $later = $this->bearer($account);
        $this->getJson("/api/v1/portal/documents/$fresh", $later)->assertStatus(410);

        [$third, $plain3] = $svc->share($patient->id, 'lab_report', $row->source_id, $account);
        $svc->revoke($third);
        $this->getJson("/api/v1/portal/documents/$plain3", $later)->assertStatus(410);
    }

    public function test_someone_else_cannot_use_a_link_and_a_withheld_result_cannot_be_shared(): void
    {
        $patient = $this->patient();
        $account = $this->account($patient);
        $row = $this->releasedReport($patient);
        [, $plain] = app(DocumentShareService::class)->share($patient->id, 'lab_report', $row->source_id, $account);

        $this->getJson("/api/v1/portal/documents/$plain", $this->bearer($this->account($this->patient())))->assertForbidden();

        app(ResultReleaseService::class)->withhold($row, $this->actor(['portal.release.manage']), 'hold');
        app('auth')->forgetGuards();
        $this->postJson('/api/v1/portal/documents/share', ['type' => 'lab_report', 'id' => $row->public_id], $this->bearer($account))->assertNotFound();
        $this->getJson("/api/v1/portal/documents/$plain", $this->bearer($account))->assertNotFound();
    }

    // ---- requests ---------------------------------------------------------------------------------------------------------------------------------------

    public function test_a_service_request_has_a_due_date_and_staff_close_it_once(): void
    {
        $patient = $this->patient();
        $headers = $this->bearer($this->account($patient));
        $id = $this->postJson('/api/v1/portal/service-requests', ['kind' => 'records_copy', 'details' => 'For my insurer'], $headers)->assertCreated()->json('data.id');
        $row = PortalServiceRequest::query()->where('public_id', $id)->first();
        $this->assertSame(now()->addDays(5)->toDateString(), $row->due_on->toDateString());

        app(ServiceRequestService::class)->update($row, $this->actor(['portal.request.manage']), 'done', 'Ready at the records desk');
        $this->assertSame(1, PortalNotification::query()->where('patient_id', $patient->id)->count());
        $this->expectException(PortalException::class);
        app(ServiceRequestService::class)->update($row->fresh(), $this->actor(['portal.request.manage']), 'rejected', null);
    }

    public function test_an_unknown_request_kind_is_refused(): void
    {
        $this->postJson('/api/v1/portal/service-requests', ['kind' => 'free_money'], $this->bearer($this->account($this->patient())))->assertStatus(422);
    }
}
