<?php

namespace Tests\Feature\Interop;

use App\Events\Clinical\EncounterCompleted;
use App\Events\PatientRegistered;
use App\Interop\Transports\Transport;
use App\Interop\Transports\TransportResult;
use App\Interop\WebhookSigner;
use App\Models\Clinical\ClinicalAlert;
use App\Models\Encounter;
use App\Models\Integration\IntegrationAccessLog;
use App\Models\Integration\IntegrationAttempt;
use App\Models\Integration\IntegrationCredential;
use App\Models\Integration\IntegrationEndpoint;
use App\Models\Integration\IntegrationMessage;
use App\Models\Integration\IntegrationPartner;
use App\Models\Integration\IntegrationRoute;
use App\Models\Patient;
use App\Services\Interop\CredentialService;
use App\Services\Interop\DeliveryWorker;
use App\Services\Interop\EventPublisher;
use App\Services\Interop\InteropException;
use App\Services\Interop\MessageService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class MessagingTest extends InteropTestCase
{
    private function endpoint($partner, string $transport = 'https', string $format = 'fhir'): IntegrationEndpoint
    {
        return IntegrationEndpoint::create(['partner_id' => $partner->id, 'name' => 'main', 'transport' => $transport, 'url' => 'https://partner.example/inbox', 'format' => $format, 'timeout_seconds' => 5, 'active' => true]);
    }

    private function route($partner, string $event, string $format = 'fhir', array $conditions = []): IntegrationRoute
    {
        return IntegrationRoute::create(['organization_id' => $this->organization->id, 'name' => "$event to {$partner->code}", 'event_key' => $event, 'partner_id' => $partner->id, 'format' => $format, 'conditions' => $conditions ?: null, 'priority' => 100, 'active' => true]);
    }

    private function fakeTransport(array $results): object
    {
        $transport = new class($results) implements Transport
        {
            public array $sent = [];

            public function __construct(public array $results) {}

            public function send(IntegrationMessage $message, IntegrationEndpoint $endpoint, IntegrationPartner $partner): TransportResult
            {
                $this->sent[] = $message->public_id;

                return array_shift($this->results) ?? TransportResult::ok(200);
            }
        };
        app(DeliveryWorker::class)->useTransport('https', $transport);

        return $transport;
    }

    public function test_the_same_key_is_stored_once_for_each_direction(): void
    {
        $partner = $this->partner();
        $svc = app(MessageService::class);
        [$a, $dupA] = $svc->enqueue($this->organization->id, $partner, 'fhir', 'Bundle', '{}', 'k1');
        [$b, $dupB] = $svc->enqueue($this->organization->id, $partner, 'fhir', 'Bundle', '{}', 'k1');
        [$in, $dupIn] = $svc->receive($this->organization->id, $partner, 'fhir', 'Bundle', '{}', 'k1');

        $this->assertFalse($dupA);
        $this->assertTrue($dupB);
        $this->assertSame($a->id, $b->id);
        $this->assertFalse($dupIn, 'the same key coming IN is a different message');
        $this->assertNotSame($a->id, $in->id);
    }

    public function test_the_payload_is_encrypted_in_the_database(): void
    {
        [$m] = app(MessageService::class)->enqueue($this->organization->id, $this->partner(), 'fhir', 'Bundle', '{"name":"Rina Akter"}', 'k');
        $this->assertStringNotContainsString('Rina', (string) DB::table('integration_messages')->where('id', $m->id)->value('payload'));
        $this->assertSame('{"name":"Rina Akter"}', $m->fresh()->payload);
    }

    public function test_a_message_is_delivered_and_the_try_is_recorded(): void
    {
        $partner = $this->partner();
        $this->endpoint($partner);
        Http::fake(['partner.example/*' => Http::response(['ok' => true], 201)]);
        [$m] = app(MessageService::class)->enqueue($this->organization->id, $partner, 'fhir', 'Bundle', '{"resourceType":"Bundle"}', 'k1');

        $this->assertSame(['sent' => 1, 'retry' => 0, 'dead' => 0], app(DeliveryWorker::class)->run());
        $this->assertSame('processed', $m->fresh()->status);
        $this->assertSame(1, $m->fresh()->attempts);
        $this->assertSame('ok', IntegrationAttempt::query()->first()->outcome);
        Http::assertSent(fn ($r) => $r->url() === 'https://partner.example/inbox' && $r->hasHeader('Content-Type', 'application/fhir+json') && $r->hasHeader('X-HN-Delivery', $m->public_id));
    }

    public function test_a_failed_message_is_retried_with_back_off_and_then_dead_lettered_and_can_be_replayed(): void
    {
        $partner = $this->partner();
        $this->endpoint($partner);
        $transport = $this->fakeTransport(array_fill(0, 6, TransportResult::retry('HTTP 503', 503)));
        [$m] = app(MessageService::class)->enqueue($this->organization->id, $partner, 'fhir', 'Bundle', '{}', 'k1');
        $worker = app(DeliveryWorker::class);

        $worker->run();
        $m->refresh();
        $this->assertSame('retrying', $m->status);
        $this->assertSame(1, $m->attempts);
        $this->assertSame(30, (int) now()->diffInSeconds($m->next_attempt_at, true), 'first wait is 30 seconds');
        $this->assertSame(0, $worker->run()['sent'] + $worker->run()['retry'], 'nothing is due yet');

        $waits = [];
        for ($i = 2; $i <= 6; $i++) {
            $this->travel(7)->hours();
            $worker->run();
            $m->refresh();
            $waits[] = $m->status === 'dead' ? 'dead' : (int) now()->diffInSeconds($m->next_attempt_at, true);
        }
        $this->assertSame([120, 600, 1800, 7200, 'dead'], $waits);
        $this->assertSame(6, $m->attempts);
        $this->assertNotNull($m->dead_at);
        $this->assertCount(6, $transport->sent);
        $this->assertSame(6, IntegrationAttempt::query()->where('message_id', $m->id)->count());

        $copy = app(MessageService::class)->replay($m, $this->actor(['interoperability.message.replay']));
        $this->assertSame('queued', $copy->status);
        $this->assertSame($m->id, $copy->replay_of_id);
        $this->assertSame($m->payload, $copy->payload);
        $this->assertSame(['sent' => 1, 'retry' => 0, 'dead' => 0], $worker->run());
        $this->assertSame('processed', $copy->fresh()->status);
    }

    public function test_a_refusal_that_cannot_be_fixed_goes_straight_to_the_dead_list_and_can_be_set_aside(): void
    {
        $partner = $this->partner();
        $this->endpoint($partner);
        $this->fakeTransport([TransportResult::refused('HTTP 422', 422)]);
        [$m] = app(MessageService::class)->enqueue($this->organization->id, $partner, 'fhir', 'Bundle', '{}', 'k1');

        $this->assertSame(['sent' => 0, 'retry' => 0, 'dead' => 1], app(DeliveryWorker::class)->run());
        $this->assertSame(1, $m->fresh()->attempts);
        $user = $this->actor(['interoperability.message.replay']);
        app(MessageService::class)->dismiss($m->fresh(), $user, 'sender was wrong');
        $this->assertSame('dismissed', $m->fresh()->status);

        $this->expectException(InteropException::class);
        app(MessageService::class)->dismiss($m->fresh(), $user, 'again');
    }

    public function test_a_dead_message_raises_an_alert_once_per_partner(): void
    {
        $partner = $this->partner();
        $this->endpoint($partner);
        $this->fakeTransport([TransportResult::refused('HTTP 400', 400), TransportResult::refused('HTTP 400', 400)]);
        foreach (['a', 'b'] as $k) {
            app(MessageService::class)->enqueue($this->organization->id, $partner, 'fhir', 'Bundle', '{}', $k);
        }
        app(DeliveryWorker::class)->run();

        $this->assertSame(1, ClinicalAlert::query()->where('category', 'integration')->count());
    }

    public function test_a_partner_that_is_not_active_is_not_sent_to(): void
    {
        $partner = $this->partner(['status' => 'paused']);
        $this->endpoint($partner);
        $transport = $this->fakeTransport([]);
        app(MessageService::class)->enqueue($this->organization->id, $partner, 'fhir', 'Bundle', '{}', 'k');

        app(DeliveryWorker::class)->run();
        $this->assertSame([], $transport->sent);
    }

    public function test_a_domain_event_is_routed_by_rule_to_the_right_partner_in_the_right_format(): void
    {
        $fhirPartner = $this->partner(['code' => 'HIE', 'requires_consent' => false]);
        $hl7Partner = $this->partner(['code' => 'LAB', 'kind' => 'hl7_peer', 'requires_consent' => false]);
        $this->route($fhirPartner, 'patient.registered', 'fhir');
        $this->route($hl7Partner, 'patient.registered', 'hl7');
        $this->route($fhirPartner, 'encounter.completed', 'fhir', ['encounter_type' => 'IPD']);

        $patient = $this->patient(['enterprise_patient_no' => 'EPT-000009']);
        event(new PatientRegistered($patient));

        $messages = IntegrationMessage::query()->where('direction', 'out')->orderBy('id')->get();
        $this->assertCount(2, $messages);
        $this->assertSame('Bundle', $messages[0]->message_type);
        $this->assertSame('fhir', $messages[0]->channel);
        $this->assertSame('Patient', json_decode($messages[0]->payload, true)['entry'][0]['resource']['resourceType']);
        $this->assertSame('hl7', $messages[1]->channel);
        $this->assertStringStartsWith('MSH|^~\\&|', $messages[1]->payload);
        $this->assertSame('patient.registered', $messages[0]->event_key);
        $this->assertSame($patient->id, $messages[0]->patient_id);
        $this->assertSame('HIE', $messages[0]->provenance['route'] === null ? '' : $fhirPartner->code, 'provenance names the rule');
    }

    public function test_routing_conditions_decide_and_a_repeated_event_queues_once(): void
    {
        $partner = $this->partner(['requires_consent' => false]);
        $this->route($partner, 'encounter.completed', 'fhir', ['encounter_type' => 'IPD']);
        $patient = $this->patient();
        $user = $this->actor([]);
        $mk = fn (string $type) => Encounter::create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id, 'encounter_no' => 'E-'.uniqid(), 'encounter_type' => $type, 'provider_id' => $user->id, 'encounter_date' => now()->toDateString(), 'status' => 'completed', 'source' => 'walk_in', 'created_by' => $user->id]);
        $opd = $mk('OPD');
        $ipd = $mk('IPD');

        event(new EncounterCompleted($opd));
        $this->assertSame(0, IntegrationMessage::query()->count());
        event(new EncounterCompleted($ipd));
        event(new EncounterCompleted($ipd));
        $this->assertSame(1, IntegrationMessage::query()->count());
    }

    public function test_a_patient_who_has_not_agreed_is_not_sent_to_a_partner_that_needs_consent(): void
    {
        $partner = $this->partner();
        $this->route($partner, 'patient.registered', 'fhir');
        $no = $this->patient();
        $yes = $this->patient();
        $this->consent($yes);

        event(new PatientRegistered($no));
        event(new PatientRegistered($yes));

        $this->assertSame([$yes->id], IntegrationMessage::query()->pluck('patient_id')->all());
        $this->assertSame(1, IntegrationAccessLog::query()->where(['patient_id' => $no->id, 'outcome' => 'denied_consent', 'action' => 'send'])->count());
    }

    public function test_the_webhook_notice_carries_ids_not_patient_details_and_is_signed(): void
    {
        $partner = $this->partner(['kind' => 'webhook', 'requires_consent' => false]);
        $this->endpoint($partner, 'https', 'json');
        [, $secret] = app(CredentialService::class)->create($partner, 'webhook-signing', 'hmac_secret');
        $this->route($partner, 'patient.registered', 'json');
        $patient = $this->patient(['first_name' => 'Rina', 'last_name' => 'Akter']);
        Http::fake(['partner.example/*' => Http::response('', 202)]);

        event(new PatientRegistered($patient));
        app(DeliveryWorker::class)->run();

        Http::assertSent(function ($r) use ($secret, $patient) {
            $body = $r->body();
            $ts = $r->header('X-HN-Timestamp')[0] ?? '';

            return str_contains($body, $patient->public_id) && ! str_contains($body, 'Akter') && ($r->header('X-HN-Signature')[0] ?? '') === 'sha256='.hash_hmac('sha256', $ts.'.'.$body, $secret);
        });
    }

    public function test_an_incoming_webhook_needs_a_good_fresh_unrepeated_signature(): void
    {
        $partner = $this->partner(['kind' => 'webhook', 'direction' => 'in']);
        [, $secret] = app(CredentialService::class)->create($partner, 'webhook-signing', 'hmac_secret');
        $signer = app(WebhookSigner::class);
        $body = json_encode(['event' => 'result.ready', 'id' => 'X1']);
        $url = "/api/v1/interoperability/webhooks/{$partner->public_id}";
        $headers = fn (string $b, ?int $ts = null, ?string $secretUsed = null, string $id = 'D1') => [config('interop.webhook.delivery_header') => $id, 'Content-Type' => 'application/json'] + $signer->sign($b, $secretUsed ?? $secret, $ts);

        $this->call('POST', $url, [], [], [], $this->server($headers($body)), $body)->assertStatus(202);
        $this->call('POST', $url, [], [], [], $this->server($headers($body)), $body)->assertStatus(401);                                       // the same delivery again
        $this->call('POST', $url, [], [], [], $this->server($headers($body.' ', null, null, 'D2')), $body)->assertStatus(401);               // the body was changed
        $this->call('POST', $url, [], [], [], $this->server($headers($body, null, 'wrong-secret', 'D3')), $body)->assertStatus(401);         // wrong secret
        $this->call('POST', $url, [], [], [], $this->server($headers($body, time() - 3600, null, 'D4')), $body)->assertStatus(401);           // too old
        $this->postJson('/api/v1/interoperability/webhooks/01NOSUCHPARTNER0000000000', [])->assertStatus(404);
        $this->assertSame(1, IntegrationMessage::query()->where('direction', 'in')->count());
    }

    private function server(array $headers): array
    {
        $server = [];
        foreach ($headers as $k => $v) {
            $server['HTTP_'.strtoupper(str_replace('-', '_', $k))] = $v;
        }

        return $server;
    }

    public function test_a_rotated_secret_keeps_working_during_the_overlap_then_stops(): void
    {
        $partner = $this->partner();
        $svc = app(CredentialService::class);
        [$v1, $old] = $svc->create($partner, 'webhook-signing', 'hmac_secret');
        [$v2, $new] = $svc->rotate($partner, 'webhook-signing', null, 24);

        $this->assertSame(2, $v2->version);
        $this->assertTrue($svc->verify($partner, 'webhook-signing', $new));
        $this->assertTrue($svc->verify($partner, 'webhook-signing', $old), 'the old secret still works inside the overlap');
        $this->assertSame($new, $svc->current($partner, 'webhook-signing')->secret);

        $this->travel(25)->hours();
        $this->assertFalse($svc->verify($partner, 'webhook-signing', $old));
        $this->assertTrue($svc->verify($partner, 'webhook-signing', $new));
        $this->assertSame(1, $svc->expireOld());
        $this->assertSame('revoked', $v1->fresh()->status);
    }

    public function test_secrets_are_encrypted_at_rest_and_never_in_a_json_dump(): void
    {
        $partner = $this->partner();
        [$cred, $plain] = app(CredentialService::class)->create($partner, 'outbound-api', 'bearer');

        $this->assertStringNotContainsString($plain, (string) DB::table('integration_credentials')->where('id', $cred->id)->value('secret'));
        $this->assertSame($plain, IntegrationCredential::query()->find($cred->id)->secret);
    }

    public function test_the_housekeeping_command_requeues_stuck_messages_and_clears_old_payloads(): void
    {
        $partner = $this->partner();
        $svc = app(MessageService::class);
        [$stuck] = $svc->enqueue($this->organization->id, $partner, 'fhir', 'Bundle', '{}', 'stuck');
        $stuck->update(['status' => 'sending']);
        [$old] = $svc->enqueue($this->organization->id, $partner, 'fhir', 'Bundle', '{"patient":"x"}', 'old');
        $old->update(['status' => 'processed', 'processed_at' => now()]);
        $this->travel(100)->days();

        $this->assertSame(0, Artisan::call('interop:check'));
        $this->assertSame('retrying', $stuck->fresh()->status);
        $this->assertNotNull($old->fresh()->purged_at);
        $this->assertSame('[purged]', $old->fresh()->payload);
    }

    public function test_a_test_send_by_a_person_queues_the_current_state_of_a_record(): void
    {
        $partner = $this->partner(['requires_consent' => false]);
        $patient = Patient::factory()->create(['organization_id' => $this->organization->id]);
        $m = app(EventPublisher::class)->sendNow($partner, 'fhir', $patient);

        $this->assertSame('queued', $m->status);
        $this->assertTrue($m->provenance['manual']);
    }
}
