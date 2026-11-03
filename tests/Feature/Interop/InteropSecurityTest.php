<?php

namespace Tests\Feature\Interop;

use App\Models\Integration\IntegrationAccessLog;
use App\Models\Integration\IntegrationMessage;
use App\Models\Integration\IntegrationPartner;
use App\Models\Organization;
use App\Models\Patient;
use App\Services\Interop\CredentialService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

/** The security test for the integration surface (spec 33–36): can a partner reach another hospital, another partner's rights, staff or patient routes; can a patient's or staff token do more than it should. */
class InteropSecurityTest extends InteropTestCase
{
    public function test_every_fhir_and_dicom_route_refuses_a_caller_without_a_token(): void
    {
        $checked = 0;
        foreach (Route::getRoutes() as $route) {
            $name = (string) $route->getName();
            if (! (str_starts_with($name, 'api.fhir.') || str_starts_with($name, 'api.dicom.')) || in_array($name, ['api.fhir.metadata'], true)) {
                continue;
            }
            $uri = '/'.str_replace(['{type}', '{id}', '{accession}'], ['Patient', '01ABCDEFGHJKMNPQRSTVWXYZ00', 'ACC1'], $route->uri());
            $res = $this->json($route->methods()[0], $uri);
            $this->assertSame(401, $res->getStatusCode(), "$name must need a token");
            $checked++;
        }
        $this->assertGreaterThan(8, $checked);
    }

    public function test_a_partner_of_one_hospital_cannot_see_another_hospitals_patients(): void
    {
        $other = Organization::factory()->create();
        $theirs = Patient::factory()->create(['organization_id' => $other->id]);
        $mine = $this->patient();
        $partner = $this->partner(['requires_consent' => false]);
        $h = $this->fhirHeaders($partner);

        $this->getJson("/api/v1/fhir/Patient/{$theirs->public_id}", $h)->assertNotFound();
        $this->getJson("/api/v1/fhir/Patient/{$theirs->public_id}/\$everything", $h)->assertNotFound();
        $this->getJson('/api/v1/fhir/Patient?family='.urlencode($theirs->last_name), $h)->assertOk()->assertJsonPath('total', 0);
        $this->getJson("/api/v1/fhir/AllergyIntolerance?patient={$theirs->public_id}", $h)->assertNotFound();
        $this->assertSame(1, $this->getJson('/api/v1/fhir/Patient', $h)->json('total'));
        $this->assertNotNull($mine);
    }

    public function test_the_hospital_is_the_partners_own_and_cannot_be_chosen_by_a_header(): void
    {
        $other = Organization::factory()->create();
        $theirs = Patient::factory()->create(['organization_id' => $other->id]);
        $h = $this->fhirHeaders($this->partner(['requires_consent' => false])) + ['X-Organization-Id' => (string) $other->id];

        $this->getJson("/api/v1/fhir/Patient/{$theirs->public_id}", $h)->assertNotFound();
    }

    public function test_a_partner_token_cannot_open_staff_patient_or_other_apis(): void
    {
        $h = $this->fhirHeaders($this->partner(), ['fhir.read', 'fhir.write', 'dicom.read']);
        foreach (['/api/v1/auth/me', '/api/v1/tenant/context', '/api/v1/search?q=a', '/api/v1/encounters', '/api/v1/portal/me', '/api/v1/portal/home'] as $url) {
            $this->assertContains($this->getJson($url, $h)->getStatusCode(), [401, 403, 404], $url);
        }
        $this->assertNotSame(200, $this->getJson('/api/v1/auth/me', $h)->getStatusCode());
    }

    public function test_a_read_only_token_cannot_write_and_a_fhir_token_cannot_use_the_scanner_api(): void
    {
        $partner = $this->partner(['requires_consent' => false]);
        $body = ['resourceType' => 'Patient', 'name' => [['family' => 'Z', 'given' => ['Z']]], 'birthDate' => '1990-01-01'];

        $this->postJson('/api/v1/fhir/Patient', $body, $this->fhirHeaders($partner, ['fhir.read']))->assertForbidden();
        $this->putJson('/api/v1/fhir/Patient/01X', $body, $this->fhirHeaders($partner, ['fhir.read']))->assertForbidden();
        $this->getJson('/api/v1/dicom/worklist', $this->fhirHeaders($partner, ['fhir.read', 'fhir.write']))->assertForbidden();
        $this->assertSame(0, Patient::query()->count());
    }

    public function test_a_patients_mobile_token_and_a_stale_partner_token_get_nothing(): void
    {
        $partner = $this->partner(['requires_consent' => false]);
        $patient = $this->patient();
        $this->getJson("/api/v1/fhir/Patient/{$patient->public_id}", $this->bearer($this->account($patient)))->assertUnauthorized();

        $h = $this->fhirHeaders($partner);
        $this->travel(91)->days();
        $this->getJson("/api/v1/fhir/Patient/{$patient->public_id}", $h)->assertUnauthorized();
    }

    public function test_a_partner_cannot_use_a_scope_it_was_not_given_even_by_asking_for_it(): void
    {
        $partner = $this->partner();
        $token = app(CredentialService::class)->issueToken($partner, ['fhir.read', 'god.mode', 'fhir.admin']);
        $abilities = $partner->tokens()->latest('id')->first()->abilities;

        $this->assertSame(['fhir.read'], $abilities);
        $this->assertNotEmpty($token);
    }

    public function test_an_injection_attempt_in_a_search_is_just_text(): void
    {
        $partner = $this->partner(['requires_consent' => false]);
        $h = $this->fhirHeaders($partner);
        $this->patient(['last_name' => 'Safe']);

        $this->getJson('/api/v1/fhir/Patient?family='.urlencode("x' OR '1'='1"), $h)->assertOk()->assertJsonPath('total', 0);
        $this->getJson('/api/v1/fhir/Patient?family='.urlencode('%'), $h)->assertOk()->assertJsonPath('total', 0);
        $this->getJson('/api/v1/fhir/Patient?birthdate='.urlencode("1990-01-01' --"), $h)->assertOk();
        $this->assertSame(1, Patient::query()->count());
    }

    public function test_the_api_never_returns_secrets_or_internal_ids(): void
    {
        $partner = $this->partner(['requires_consent' => false]);
        [, $secret] = app(CredentialService::class)->create($partner, 'webhook-signing', 'hmac_secret');
        $patient = $this->patient();
        $this->labReport($patient);
        $h = $this->fhirHeaders($partner);

        $body = $this->getJson("/api/v1/fhir/Patient/{$patient->public_id}/\$everything", $h)->assertOk()->getContent().$this->getJson('/api/v1/fhir/metadata')->getContent();
        foreach ([$secret, 'password', 'remember_token', 'token_hash', '"organization_id"', 'payload_hash'] as $needle) {
            $this->assertStringNotContainsString($needle, $body, "leaked $needle");
        }
        $this->assertStringNotContainsString('"id":"'.$patient->id.'"', $body);
        $this->assertDoesNotMatchRegularExpression('/"reference":"[A-Za-z]+\/\d+"/', $body, 'no reference uses a database number');
    }

    public function test_access_to_the_log_is_append_only_in_use_and_a_denied_read_is_visible(): void
    {
        $partner = $this->partner();
        $patient = $this->patient();
        $this->getJson("/api/v1/fhir/Patient/{$patient->public_id}", $this->fhirHeaders($partner))->assertForbidden();

        $row = IntegrationAccessLog::query()->where('patient_id', $patient->id)->firstOrFail();
        $this->assertSame('denied_consent', $row->outcome);
        $this->assertSame($partner->id, $row->partner_id);
        $this->assertFalse(in_array('updated_at', array_keys($row->getAttributes()), true), 'the table has no update timestamp: rows are written once');
    }

    public function test_the_benchmark_runs_and_leaves_nothing_behind(): void
    {
        $this->patient();
        $before = IntegrationMessage::query()->count();
        $this->assertSame(0, Artisan::call('interop:benchmark', ['--n' => 20, '--organization' => $this->organization->id]));
        $this->assertStringContainsString('read an HL7 message', Artisan::output());
        $this->assertSame($before, IntegrationMessage::query()->count());
        $this->assertNotNull(IntegrationPartner::query()->count());
    }
}
