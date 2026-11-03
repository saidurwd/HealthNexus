<?php

namespace Tests\Feature\Portal;

use App\Models\Appointment;
use App\Models\Crm\CrmComplaint;
use App\Models\Organization;
use App\Models\Portal\PortalAccessLog;
use App\Models\Portal\PortalDocument;
use App\Models\Portal\PortalMessage;
use App\Models\Portal\PortalPayment;
use App\Models\Portal\PortalRefillRequest;
use App\Models\Portal\PortalResultRelease;
use App\Models\Portal\PortalThread;
use App\Models\User;
use App\Services\Crm\ComplaintService;
use App\Services\Portal\DocumentShareService;
use App\Services\Portal\MessagingService;
use App\Services\Portal\PortalPaymentService;
use App\Services\Portal\ResultReleaseService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

/** The critical security test for the patient portal and the mobile API (spec 79): can one patient reach another's data, can a token do more than it should, can the login be guessed or mass-assigned. */
class PortalSecurityTest extends PortalTestCase
{
    public function test_every_patient_api_route_refuses_a_caller_who_is_not_signed_in(): void
    {
        $open = ['api.portal.config', 'api.portal.register.start', 'api.portal.register.complete', 'api.portal.invitation', 'api.portal.login', 'api.portal.login.otp', 'api.portal.password.forgot', 'api.portal.password.reset', 'api.portal.payments.callback'];
        $checked = 0;
        foreach (Route::getRoutes() as $route) {
            $name = (string) $route->getName();
            if (! str_starts_with($name, 'api.portal.') || in_array($name, $open, true)) {
                continue;
            }
            $uri = '/'.str_replace('{device}', '1', preg_replace('/\{(?!device)[^}]+\}/', '01ABCDEFGHJKMNPQRSTVWXYZ00', $route->uri()));
            $res = $this->json($route->methods()[0], $uri);
            $this->assertSame(401, $res->getStatusCode(), "$name must need a patient login");
            $checked++;
        }
        $this->assertGreaterThan(40, $checked);
    }

    public function test_one_patient_cannot_reach_anothers_records_through_any_id(): void
    {
        $victim = $this->patient();
        $victimAccount = $this->account($victim);
        $attacker = $this->bearer($this->account($this->patient()));

        $appointment = Appointment::factory()->create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $victim->id, 'appointment_date' => now()->addDays(3)->toDateString(), 'appointment_time' => '10:00', 'status' => 'completed']);
        $this->labReport($victim);
        app(ResultReleaseService::class)->sync();
        $release = PortalResultRelease::query()->first();
        app(ResultReleaseService::class)->release($release, $this->actor(['portal.release.manage']));
        $rx = $this->prescription($victim);
        $invoice = $this->invoice($victim);
        $thread = app(MessagingService::class)->start($victimAccount, $victim, 'Private', 'general', 'private words');
        $complaint = app(ComplaintService::class)->lodge($this->organization->id, ['category' => 'billing', 'description' => 'x'], null, $victim);
        $payment = app(PortalPaymentService::class)->initiate($victimAccount, $victim, $invoice, 'bkash', 'k');
        [, $plain] = app(DocumentShareService::class)->share($victim->id, 'lab_report', $release->source_id, $victimAccount);

        $gets = ["/appointments/{$appointment->public_id}/queue", "/appointments/{$appointment->public_id}/video", "/results/{$release->public_id}", "/invoices/{$invoice->public_id}", "/payments/{$payment->public_id}", "/threads/{$thread->public_id}"];
        foreach ($gets as $path) {
            $this->getJson("/api/v1/portal$path", $attacker)->assertNotFound();
        }
        $posts = ["/appointments/{$appointment->public_id}/cancel" => [], "/appointments/{$appointment->public_id}/feedback" => ['rating' => 5], "/prescriptions/{$rx->public_id}/refill" => [], "/invoices/{$invoice->public_id}/pay" => ['method' => 'bkash'],
            "/threads/{$thread->public_id}/reply" => ['body' => 'hi'], "/complaints/{$complaint->number}/rating" => ['score' => 5]];
        foreach ($posts as $path => $body) {
            $this->postJson("/api/v1/portal$path", $body, $attacker + ['Idempotency-Key' => 'x'])->assertNotFound();
        }
        $this->getJson("/api/v1/portal/documents/$plain", $attacker)->assertForbidden();
        $this->getJson("/api/v1/portal/results?patient={$victim->public_id}", $attacker)->assertForbidden();
        $this->getJson("/api/v1/portal/invoices?patient={$victim->public_id}", $attacker)->assertForbidden();
        $this->getJson("/api/v1/portal/records/summary?patient={$victim->public_id}", $attacker)->assertForbidden();

        $this->assertSame('completed', $appointment->fresh()->status);
        $this->assertSame(0, PortalRefillRequest::query()->count());
        $this->assertSame(0, PortalMessage::query()->where('body', 'hi')->count());
    }

    public function test_a_patient_token_cannot_be_used_on_staff_routes_and_a_staff_token_not_on_patient_routes(): void
    {
        $headers = $this->bearer($this->account());
        foreach (['/api/v1/auth/me', '/api/v1/tenant/context', '/api/v1/search?q=a', '/api/v1/encounters', '/api/v1/codes/search?q=a'] as $url) {
            $this->getJson($url, $headers)->assertStatus(403);
        }
        $this->postJson('/api/v1/auth/logout', [], $headers)->assertForbidden();

        Sanctum::actingAs(User::factory()->create(), ['*']);
        $this->getJson('/api/v1/portal/me')->assertUnauthorized();
        $this->getJson('/api/v1/portal/results')->assertUnauthorized();
    }

    public function test_a_token_with_another_ability_does_not_open_the_patient_api(): void
    {
        $account = $this->account();
        $token = $account->createToken('odd', ['something-else'])->plainTextToken;
        $this->getJson('/api/v1/portal/me', ['Authorization' => "Bearer $token"])->assertUnauthorized();
    }

    public function test_an_expired_token_is_refused(): void
    {
        $headers = $this->bearer($this->account());
        $this->travel(31)->days();
        $this->getJson('/api/v1/portal/me', $headers)->assertUnauthorized();
    }

    public function test_extra_fields_in_a_request_cannot_change_who_owns_a_record(): void
    {
        $me = $this->patient();
        $other = $this->patient();
        $headers = $this->bearer($this->account($me));
        $otherOrg = Organization::factory()->create();

        $this->postJson('/api/v1/portal/threads', ['subject' => 'Hi', 'body' => 'Hello', 'patient_id' => $other->id, 'organization_id' => $otherOrg->id, 'status' => 'closed', 'urgent' => true], $headers)->assertCreated();
        $thread = PortalThread::query()->first();
        $this->assertSame($me->id, $thread->patient_id);
        $this->assertSame($this->organization->id, $thread->organization_id);
        $this->assertSame('open', $thread->status);

        $this->postJson('/api/v1/portal/complaints', ['category' => 'billing', 'description' => 'x', 'organization_id' => $otherOrg->id, 'patient_id' => $other->id, 'status' => 'closed', 'assigned_to' => 1], $headers)->assertCreated();
        $c = CrmComplaint::query()->first();
        $this->assertSame($this->organization->id, $c->organization_id);
        $this->assertSame($me->id, $c->patient_id);
        $this->assertSame('open', $c->status);
        $this->assertNull($c->assigned_to);
    }

    public function test_the_amount_and_the_outcome_of_a_payment_cannot_be_dictated_by_the_caller(): void
    {
        $patient = $this->patient();
        $headers = $this->bearer($this->account($patient));
        $invoice = $this->invoice($patient, '500.00');

        $this->postJson("/api/v1/portal/invoices/{$invoice->public_id}/pay", ['method' => 'bkash', 'amount' => '900'], $headers + ['Idempotency-Key' => 'a'])->assertStatus(422);
        $res = $this->postJson("/api/v1/portal/invoices/{$invoice->public_id}/pay", ['method' => 'bkash', 'status' => 'succeeded', 'billing_payment_id' => 1], $headers + ['Idempotency-Key' => 'b'])->assertCreated();
        $this->assertSame('pending', $res->json('data.status'));
        $this->assertSame('finalized', $invoice->fresh()->status);
        $this->assertNull(PortalPayment::query()->first()->billing_payment_id);
    }

    public function test_a_slot_token_that_was_changed_or_belongs_to_another_hospital_is_refused(): void
    {
        $headers = $this->bearer($this->account());
        $slot = $this->slot($this->provider());
        $good = Crypt::encryptString((string) $slot->id);

        $this->postJson('/api/v1/portal/appointments', ['slot' => substr($good, 0, -4).'AAAA'], $headers)->assertStatus(422);
        $this->postJson('/api/v1/portal/appointments', ['slot' => (string) $slot->id], $headers)->assertStatus(422);

        $other = Organization::factory()->create();
        $slot->update(['organization_id' => $other->id]);
        $this->postJson('/api/v1/portal/appointments', ['slot' => $good], $headers)->assertStatus(422);
        $this->assertSame(0, Appointment::query()->count());
    }

    public function test_responses_never_carry_secrets(): void
    {
        $account = $this->account();
        $headers = $this->bearer($account);
        $account->devices()->first()->update(['push_token' => 'SECRET-PUSH']);

        $bodies = [
            $this->getJson('/api/v1/portal/me', $headers)->getContent(), $this->getJson('/api/v1/portal/auth/devices', $headers)->getContent(), $this->getJson('/api/v1/portal/home', $headers)->getContent(),
            $this->getJson('/api/v1/portal/family', $headers)->getContent(), $this->getJson('/api/v1/portal/me/export', $headers)->getContent(),
        ];
        foreach ($bodies as $body) {
            foreach (['password', 'remember_token', 'invitation_hash', 'SECRET-PUSH', 'device_hash', 'token_hash', 'code_hash', 'idempotency_key'] as $needle) {
                $this->assertStringNotContainsString($needle, $body, "leaked $needle");
            }
        }
        $this->assertStringNotContainsString('"id":1,', $bodies[0]);
    }

    public function test_wrong_codes_cannot_be_guessed_and_a_used_code_cannot_be_used_again(): void
    {
        $patient = $this->patient(['phone' => '01712345678']);
        $start = $this->postJson('/api/v1/portal/auth/register/start', ['identifier' => $patient->enterprise_patient_no, 'date_of_birth' => '1990-04-12', 'phone' => '01712345678'])->json('data.challenge');
        $code = $this->lastCode('8801712345678');
        $device = ['uid' => 'd1', 'name' => 'x', 'platform' => 'android'];
        $body = ['challenge' => $start, 'password' => 'Strong-Pass-1', 'accept_terms' => true, 'device' => $device];

        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/v1/portal/auth/register/complete', $body + ['code' => '99999'.$i])->assertStatus(422);
        }
        $this->postJson('/api/v1/portal/auth/register/complete', $body + ['code' => $code])->assertStatus(422); // five tries burn the code even for the right guess

        $this->travel(2)->minutes();
        $start2 = $this->postJson('/api/v1/portal/auth/register/start', ['identifier' => $patient->enterprise_patient_no, 'date_of_birth' => '1990-04-12', 'phone' => '01712345678'])->json('data.challenge');
        $code2 = $this->lastCode('8801712345678');
        $this->postJson('/api/v1/portal/auth/register/complete', ['challenge' => $start2, 'code' => $code2] + $body)->assertCreated();
        $this->postJson('/api/v1/portal/auth/register/complete', ['challenge' => $start2, 'code' => $code2] + $body)->assertStatus(422);
    }

    public function test_the_login_endpoint_does_not_reveal_which_numbers_have_an_account(): void
    {
        $this->account($this->patient(['phone' => '01712345678']));
        $dev = ['uid' => 'd', 'name' => 'x', 'platform' => 'android'];
        $known = $this->postJson('/api/v1/portal/auth/login', ['phone' => '01712345678', 'password' => 'wrong-pass-1', 'device' => $dev]);
        $unknown = $this->postJson('/api/v1/portal/auth/login', ['phone' => '01799999999', 'password' => 'wrong-pass-1', 'device' => $dev]);

        $this->assertSame($known->getStatusCode(), $unknown->getStatusCode());
        $this->assertSame($known->json('message'), $unknown->json('message'));
        $reset1 = $this->postJson('/api/v1/portal/auth/password/forgot', ['phone' => '01712345678']);
        $reset2 = $this->postJson('/api/v1/portal/auth/password/forgot', ['phone' => '01799999999']);
        $this->assertSame($reset1->getStatusCode(), $reset2->getStatusCode());
        $this->assertSame($reset1->json('message'), $reset2->json('message'));
        $this->assertSame(array_keys($reset1->json('data')), array_keys($reset2->json('data')));
    }

    public function test_a_message_with_a_script_is_shown_to_staff_as_text(): void
    {
        $patient = $this->patient();
        $headers = $this->bearer($this->account($patient));
        $id = $this->postJson('/api/v1/portal/threads', ['subject' => 'x', 'body' => '<script>alert("hacked")</script><img src=x onerror=alert(1)>hello'], $headers)->json('data.id');

        $staff = $this->superAdmin();
        $this->actingAs($staff);
        session()->put(['tenant_organization_id' => $this->organization->id, 'tenant_branch_id' => $this->branch->id]);
        $html = $this->get(route('admin.portal.threads.show', $id))->assertOk()->getContent();
        $this->assertStringNotContainsString('<script>alert', $html);
        $this->assertStringNotContainsString('onerror=alert', $html);
    }

    public function test_logging_in_on_the_web_starts_a_fresh_session_and_a_closed_login_cannot_come_back(): void
    {
        $account = $this->account($this->patient(['phone' => '01712345678']));
        $this->withCookie('portal_device', 'b1');
        $this->post('/portal/login', ['phone' => '01712345678', 'password' => 'Portal-Pass-123']);
        $challenge = session('challenge');
        $before = session()->getId();
        $this->post('/portal/login/code', ['challenge' => $challenge, 'code' => $this->lastCode('8801712345678')])->assertRedirect('/portal/home');
        $this->assertNotSame($before, session()->getId());

        $this->post('/portal/settings/close', ['password' => 'Portal-Pass-123'])->assertRedirect('/portal/login');
        $this->assertSame('closed', $account->fresh()->status);
        $this->get('/portal/home')->assertRedirect('/portal/login');
        $this->post('/portal/login', ['phone' => '01712345678', 'password' => 'Portal-Pass-123'])->assertSessionHas('error');
    }

    public function test_closing_a_login_signs_every_phone_out_and_keeps_the_hospital_record(): void
    {
        $patient = $this->patient();
        $account = $this->account($patient);
        $headers = $this->bearer($account);
        $this->postJson('/api/v1/portal/me/close', ['password' => 'wrong'], $headers)->assertStatus(422);
        $this->postJson('/api/v1/portal/me/close', ['password' => 'Portal-Pass-123'], $headers)->assertOk();

        $this->getJson('/api/v1/portal/me', $headers)->assertUnauthorized();
        $this->assertNotNull($patient->fresh());
        $this->assertSame('closed', $account->fresh()->status);
    }

    public function test_the_export_is_the_persons_own_data_only_and_is_logged(): void
    {
        $me = $this->patient(['first_name' => 'Mine']);
        $other = $this->patient(['first_name' => 'Theirs']);
        $this->invoice($other);
        $headers = $this->bearer($this->account($me));
        $body = $this->getJson('/api/v1/portal/me/export', $headers)->assertOk()->getContent();

        $this->assertStringContainsString('Mine', $body);
        $this->assertStringNotContainsString('Theirs', $body);
        $this->assertSame(1, PortalAccessLog::query()->where(['action' => 'export', 'patient_id' => $me->id])->count());
    }

    public function test_the_sync_endpoint_lists_only_what_the_caller_may_see(): void
    {
        $me = $this->patient();
        $other = $this->patient();
        $mine = $this->invoice($me);
        $this->invoice($other);
        $headers = $this->bearer($this->account($me));

        $this->getJson('/api/v1/portal/sync?since='.urlencode(now()->subDay()->toIso8601String()), $headers)->assertOk()->assertJsonPath('data.invoices', [$mine->public_id]);
    }

    public function test_document_links_cannot_be_guessed(): void
    {
        $patient = $this->patient();
        $account = $this->account($patient);
        $this->labReport($patient);
        app(ResultReleaseService::class)->sync();
        $row = PortalResultRelease::query()->first();
        app(ResultReleaseService::class)->release($row, $this->actor(['portal.release.manage']));
        [, $plain] = app(DocumentShareService::class)->share($patient->id, 'lab_report', $row->source_id, $account);

        $this->assertSame(48, strlen($plain));
        $this->assertSame(hash('sha256', $plain), PortalDocument::query()->first()->token_hash);
        $this->getJson('/api/v1/portal/documents/'.substr($plain, 0, -1).(str_ends_with($plain, 'x') ? 'y' : 'x'), $this->bearer($account))->assertStatus(410);
    }
}
