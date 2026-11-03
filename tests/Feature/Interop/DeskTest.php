<?php

namespace Tests\Feature\Interop;

use App\Models\Integration\Hl7InboundResult;
use App\Models\Integration\IntegrationAccessLog;
use App\Models\Integration\IntegrationEndpoint;
use App\Models\Integration\IntegrationMessage;
use App\Models\Integration\IntegrationPartner;
use App\Models\Integration\IntegrationRoute;
use App\Models\Organization;
use App\Models\Patient;
use App\Services\Interop\CredentialService;
use App\Services\Interop\DicomWorklistService;
use App\Services\Interop\IdMapper;
use App\Services\Interop\InteropPolicy;
use App\Services\Interop\MessageService;
use App\Services\TenantContextResolver;

class DeskTest extends InteropTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->superAdmin());
        session()->put(['tenant_organization_id' => $this->organization->id, 'tenant_branch_id' => $this->branch->id]);
        app(TenantContextResolver::class)->setOrganizationId($this->organization->id);
    }

    private function seedEverything(): array
    {
        $partner = $this->partner(['kind' => 'hl7_peer', 'code' => 'LAB1', 'requires_consent' => false]);
        [, $secret] = app(CredentialService::class)->create($partner, 'webhook-signing', 'hmac_secret');
        $endpoint = IntegrationEndpoint::create(['partner_id' => $partner->id, 'name' => 'main', 'transport' => 'https', 'url' => 'https://x.example/in', 'format' => 'fhir', 'active' => true]);
        IntegrationRoute::create(['organization_id' => $this->organization->id, 'name' => 'r', 'event_key' => 'patient.registered', 'partner_id' => $partner->id, 'format' => 'fhir', 'active' => true, 'priority' => 100]);
        $patient = $this->patient();
        [$ok] = app(MessageService::class)->enqueue($this->organization->id, $partner, 'fhir', 'Bundle', '{"name":"Rina"}', 'a', ['patient_id' => $patient->id]);
        $ok->update(['status' => 'processed', 'processed_at' => now(), 'attempts' => 1]);
        [$dead] = app(MessageService::class)->enqueue($this->organization->id, $partner, 'hl7', 'ADT^A04', 'MSH|x', 'b', ['patient_id' => $patient->id]);
        $dead->update(['status' => 'dead', 'dead_at' => now(), 'attempts' => 6, 'error' => 'HTTP 503']);
        $msg = IntegrationMessage::create(['organization_id' => $this->organization->id, 'partner_id' => $partner->id, 'direction' => 'in', 'channel' => 'hl7', 'message_type' => 'ORU^R01', 'idempotency_key' => 'z', 'status' => 'processed', 'payload' => 'x', 'payload_hash' => 'h']);
        $result = Hl7InboundResult::create(['organization_id' => $this->organization->id, 'message_id' => $msg->id, 'patient_id' => $patient->id, 'code' => 'HB', 'name' => 'Hemoglobin', 'value' => '9.1', 'unit' => 'g/dL', 'abnormal_flag' => 'L', 'status' => 'new']);
        $this->radiologyExam($patient);
        app(DicomWorklistService::class)->sync($this->organization->id);
        $this->get('/api/v1/fhir/metadata'); // nothing
        IntegrationAccessLog::create(['organization_id' => $this->organization->id, 'partner_id' => $partner->id, 'patient_id' => $patient->id, 'resource_type' => 'Patient', 'action' => 'read', 'outcome' => 'denied_consent', 'created_at' => now()]);
        app(IdMapper::class)->link('LAB1', 'Patient', $patient->id, 'LAB-77', $this->organization->id);

        return compact('partner', 'dead', 'result', 'patient', 'secret', 'endpoint');
    }

    public function test_every_screen_opens_with_data_in_it(): void
    {
        $x = $this->seedEverything();
        $urls = ['admin.interop.dashboard' => [], 'admin.interop.partners' => [], 'admin.interop.partners.show' => [$x['partner']->public_id], 'admin.interop.messages' => [], 'admin.interop.messages.show' => [$x['dead']->public_id],
            'admin.interop.results' => [], 'admin.interop.access-log' => [], 'admin.interop.worklist' => [], 'admin.interop.national' => [], 'admin.interop.idmaps' => [], 'admin.interop.terminology' => [], 'admin.interop.settings' => []];
        foreach ($urls as $name => $params) {
            $this->get(route($name, $params))->assertOk();
        }
        $this->get(route('admin.interop.messages.show', $x['dead']->public_id))->assertSee('HTTP 503')->assertSee('MSH|x');
        $this->get(route('admin.interop.idmaps'))->assertSee('LAB-77');
    }

    public function test_without_permission_every_screen_is_refused_and_another_hospitals_partner_is_not_found(): void
    {
        $x = $this->seedEverything();
        $other = IntegrationPartner::factory()->create(['organization_id' => Organization::factory()->create()->id]);
        $this->get(route('admin.interop.partners.show', $other->public_id))->assertNotFound();

        $this->actingAs($this->actor([]));
        foreach (['admin.interop.dashboard', 'admin.interop.partners', 'admin.interop.messages', 'admin.interop.results', 'admin.interop.access-log', 'admin.interop.worklist', 'admin.interop.national', 'admin.interop.idmaps', 'admin.interop.terminology', 'admin.interop.settings'] as $name) {
            $this->get(route($name))->assertForbidden();
        }
        $this->get(route('admin.interop.partners.show', $x['partner']->public_id))->assertForbidden();
    }

    public function test_the_message_content_is_shown_only_to_those_who_may_send_again(): void
    {
        $x = $this->seedEverything();
        $viewer = $this->actor(['interoperability.message.view']);
        $this->actingAs($viewer);
        $this->get(route('admin.interop.messages.show', $x['dead']->public_id))->assertOk()->assertDontSee('MSH|x')->assertSee('holds patient details');
    }

    public function test_a_person_replays_a_dead_message_and_sets_another_aside(): void
    {
        $x = $this->seedEverything();
        $this->post(route('admin.interop.messages.act', $x['dead']->public_id), ['action' => 'replay'])->assertRedirect();
        $this->assertSame(1, IntegrationMessage::query()->where('replay_of_id', $x['dead']->id)->count());

        $this->post(route('admin.interop.messages.act', $x['dead']->public_id), ['action' => 'dismiss'])->assertSessionHasErrors('reason');
        $this->post(route('admin.interop.messages.act', $x['dead']->public_id), ['action' => 'dismiss', 'reason' => 'obsolete'])->assertRedirect();
        $this->assertSame('dismissed', $x['dead']->fresh()->status);
    }

    public function test_results_from_outside_are_reviewed_once(): void
    {
        $x = $this->seedEverything();
        $this->post(route('admin.interop.results.review', $x['result']->public_id), ['decision' => 'accepted', 'note' => 'entered by the lab'])->assertRedirect();
        $this->assertSame('accepted', $x['result']->fresh()->status);
        $this->assertNotNull($x['result']->fresh()->reviewed_by);
        $this->post(route('admin.interop.results.review', $x['result']->public_id), ['decision' => 'rejected'])->assertSessionHas('error');
        $this->assertSame('accepted', $x['result']->fresh()->status);
    }

    public function test_a_partner_is_added_switched_on_given_a_secret_a_token_an_endpoint_and_a_rule(): void
    {
        $this->post(route('admin.interop.partners.store'), ['name' => 'City Hospital', 'code' => 'CITY', 'kind' => 'fhir_client', 'direction' => 'both', 'profile' => 'bd-core', 'consent_type' => 'data_sharing', 'requires_consent' => '1'])->assertRedirect();
        $p = IntegrationPartner::query()->where('code', 'CITY')->firstOrFail();
        $this->assertSame('draft', $p->status);
        $this->post(route('admin.interop.partners.store'), ['name' => 'Again', 'code' => 'CITY', 'kind' => 'fhir_client', 'direction' => 'both', 'profile' => 'r4'])->assertSessionHasErrors('code');

        $this->post(route('admin.interop.partners.update', $p->public_id), ['status' => 'active'])->assertRedirect();
        $this->post(route('admin.interop.partners.credentials', $p->public_id), ['action' => 'create', 'label' => 'webhook-signing', 'kind' => 'hmac_secret'])->assertRedirect()->assertSessionHas('interop_secret');
        $this->post(route('admin.interop.partners.credentials', $p->public_id), ['action' => 'rotate', 'label' => 'webhook-signing'])->assertRedirect()->assertSessionHas('interop_secret');
        $this->post(route('admin.interop.partners.credentials', $p->public_id), ['action' => 'rotate', 'label' => 'nothing-here'])->assertSessionHasErrors('label');
        $this->assertSame(['active', 'retiring'], $p->credentials()->orderByDesc('version')->pluck('status')->all());
        $this->post(route('admin.interop.partners.tokens', $p->public_id), ['action' => 'issue', 'abilities' => ['fhir.read']])->assertRedirect()->assertSessionHas('interop_token');
        $this->assertSame(1, $p->tokens()->count());
        $this->post(route('admin.interop.partners.endpoints', $p->public_id), ['name' => 'inbox', 'transport' => 'https', 'url' => 'http://insecure.example', 'format' => 'fhir'])->assertSessionHasErrors('url');
        $this->post(route('admin.interop.partners.endpoints', $p->public_id), ['name' => 'inbox', 'transport' => 'https', 'url' => 'https://city.example/fhir', 'format' => 'fhir'])->assertRedirect();
        $this->post(route('admin.interop.partners.routes', $p->public_id), ['action' => 'create', 'event_key' => 'encounter.completed', 'format' => 'fhir', 'condition_key' => 'encounter_type', 'condition_value' => 'IPD'])->assertRedirect();
        $this->assertSame(['encounter_type' => 'IPD'], IntegrationRoute::query()->where('partner_id', $p->id)->first()->conditions);

        $this->get(route('admin.interop.partners.show', $p->public_id))->assertOk()->assertSee('City Hospital');
        $this->post(route('admin.interop.partners.update', $p->public_id), ['status' => 'retired'])->assertRedirect();
        $this->assertSame(0, $p->tokens()->count(), 'retiring a partner revokes its tokens');
    }

    public function test_a_test_send_obeys_consent_and_a_manual_link_cannot_steal_a_partner_number(): void
    {
        $partner = $this->partner(['code' => 'HIE']);
        $yes = $this->patient();
        $no = $this->patient();
        $this->consent($yes);

        $this->post(route('admin.interop.partners.test-send', $partner->public_id), ['patient_no' => $no->enterprise_patient_no, 'format' => 'fhir'])->assertSessionHasErrors('patient_no');
        $this->post(route('admin.interop.partners.test-send', $partner->public_id), ['patient_no' => $yes->enterprise_patient_no, 'format' => 'fhir'])->assertSessionHas('success');
        $this->assertSame(1, IntegrationMessage::query()->where('patient_id', $yes->id)->count());

        $this->post(route('admin.interop.idmaps.link'), ['partner' => 'HIE', 'patient_no' => $yes->enterprise_patient_no, 'external_id' => 'X-1'])->assertRedirect();
        $this->post(route('admin.interop.idmaps.link'), ['partner' => 'HIE', 'patient_no' => $no->enterprise_patient_no, 'external_id' => 'X-1'])->assertSessionHasErrors('external_id');
    }

    public function test_the_national_screen_shares_with_consent_and_otherwise_explains(): void
    {
        config(['interop.national.driver' => 'sandbox']);
        $exchange = $this->partner(['kind' => 'national_exchange', 'consent_type' => 'national_sharing']);
        $patient = $this->patient();

        $this->post(route('admin.interop.national.share'), ['patient_no' => $patient->enterprise_patient_no])->assertSessionHasErrors('patient_no');
        $this->consent($patient, 'national_sharing');
        $this->post(route('admin.interop.national.share'), ['patient_no' => $patient->enterprise_patient_no])->assertSessionHas('success');
        $this->assertSame(1, IntegrationMessage::query()->where('partner_id', $exchange->id)->count());
    }

    public function test_settings_are_saved_and_used(): void
    {
        $this->post(route('admin.interop.settings.save'), ['max_attempts' => 3, 'backoff' => '10, 20, 30', 'overlap_hours' => 2, 'token_days' => 30, 'processed_days' => 10, 'dead_days' => 20, 'access_days' => 60, 'page_size' => 10, 'tolerance' => 120])->assertRedirect();
        $this->assertSame(3, app(InteropPolicy::class)->int('delivery.max_attempts'));
        $this->assertSame([10, 20, 30], app(InteropPolicy::class)->get('delivery.backoff_seconds'));
        $this->post(route('admin.interop.settings.save'), ['max_attempts' => 3, 'backoff' => 'soon', 'overlap_hours' => 2, 'token_days' => 30, 'processed_days' => 10, 'dead_days' => 20, 'access_days' => 60, 'page_size' => 10, 'tolerance' => 120])->assertSessionHasErrors('backoff');
        $this->assertNotNull(Patient::query()->count());
    }
}
