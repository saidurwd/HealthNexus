<?php

namespace Tests\Feature\Interop;

use App\Models\Integration\IntegrationAccessLog;
use App\Models\Patient;
use App\Models\PatientAllergy;
use App\Models\PatientConsent;
use App\Models\Terminology\CodeConcept;
use App\Models\Terminology\CodeMap;
use App\Models\Terminology\CodeSystem;
use App\Services\Interop\CredentialService;

class FhirApiTest extends InteropTestCase
{
    public function test_the_capability_statement_is_public_and_lists_what_is_served(): void
    {
        $res = $this->getJson('/api/v1/fhir/metadata')->assertOk()->assertHeader('Content-Type', 'application/fhir+json');
        $this->assertSame('CapabilityStatement', $res->json('resourceType'));
        $this->assertSame('4.0.1', $res->json('fhirVersion'));
        $types = array_column($res->json('rest.0.resource'), 'type');
        foreach (['Patient', 'Encounter', 'Observation', 'DiagnosticReport', 'ImagingStudy', 'MedicationRequest', 'Coverage'] as $t) {
            $this->assertContains($t, $types);
        }
    }

    public function test_a_patient_is_read_by_a_partner_only_with_the_patients_consent_and_every_try_is_logged(): void
    {
        $partner = $this->partner();
        $headers = $this->fhirHeaders($partner);
        $patient = $this->patient(['first_name' => 'Rina', 'last_name' => 'Akter', 'sex' => 'F', 'date_of_birth' => '1990-04-12']);

        $this->getJson("/api/v1/fhir/Patient/{$patient->public_id}", $headers)->assertForbidden()->assertJsonPath('resourceType', 'OperationOutcome')->assertJsonPath('issue.0.code', 'forbidden');
        $this->consent($patient);
        $res = $this->getJson("/api/v1/fhir/Patient/{$patient->public_id}", $headers)->assertOk();

        $this->assertSame('Patient', $res->json('resourceType'));
        $this->assertSame($patient->public_id, $res->json('id'));
        $this->assertSame('Akter', $res->json('name.0.family'));
        $this->assertSame('female', $res->json('gender'));
        $this->assertSame('1990-04-12', $res->json('birthDate'));
        $this->assertSame($patient->enterprise_patient_no, $res->json('identifier.0.value'));
        $this->assertStringNotContainsString('"id":'.$patient->id.',', $res->getContent(), 'no numeric id');
        $this->assertSame(['denied_consent', 'allowed'], IntegrationAccessLog::query()->where('patient_id', $patient->id)->orderBy('id')->pluck('outcome')->all());
    }

    public function test_a_partner_that_needs_no_consent_reads_without_it(): void
    {
        $partner = $this->partner(['requires_consent' => false, 'consent_type' => null]);
        $patient = $this->patient();
        $this->getJson("/api/v1/fhir/Patient/{$patient->public_id}", $this->fhirHeaders($partner))->assertOk();
    }

    public function test_withdrawn_consent_closes_the_door_again(): void
    {
        $partner = $this->partner();
        $headers = $this->fhirHeaders($partner);
        $patient = $this->patient();
        $this->consent($patient);
        $this->getJson("/api/v1/fhir/Patient/{$patient->public_id}", $headers)->assertOk();
        PatientConsent::query()->where('patient_id', $patient->id)->update(['status' => 'withdrawn']);

        $this->getJson("/api/v1/fhir/Patient/{$patient->public_id}", $headers)->assertForbidden();
    }

    public function test_unknown_and_other_hospitals_records_are_not_found_as_operation_outcomes(): void
    {
        $partner = $this->partner();
        $headers = $this->fhirHeaders($partner, ['fhir.read']);
        $other = Patient::factory()->create();

        $this->getJson('/api/v1/fhir/Patient/01NOSUCHPATIENT', $headers)->assertNotFound()->assertJsonPath('resourceType', 'OperationOutcome')->assertJsonPath('issue.0.code', 'not-found');
        $this->getJson("/api/v1/fhir/Patient/{$other->public_id}", $headers)->assertNotFound();
        $this->getJson('/api/v1/fhir/Nonsense/1', $headers)->assertNotFound()->assertJsonPath('issue.0.code', 'not-supported');
    }

    public function test_a_clinical_search_needs_a_patient_and_returns_a_bundle_with_paging(): void
    {
        $partner = $this->partner(['requires_consent' => false]);
        $headers = $this->fhirHeaders($partner);
        $patient = $this->patient();
        foreach (range(1, 5) as $i) {
            PatientAllergy::create(['organization_id' => $this->organization->id, 'patient_id' => $patient->id, 'substance' => "Allergen $i", 'severity' => 'mild', 'is_active' => true]);
        }

        $this->getJson('/api/v1/fhir/AllergyIntolerance', $headers)->assertStatus(400)->assertJsonPath('issue.0.code', 'required');
        $page = $this->getJson("/api/v1/fhir/AllergyIntolerance?patient={$patient->public_id}&_count=2", $headers)->assertOk();
        $this->assertSame('Bundle', $page->json('resourceType'));
        $this->assertSame('searchset', $page->json('type'));
        $this->assertSame(5, $page->json('total'));
        $this->assertCount(2, $page->json('entry'));
        $this->assertSame('next', $page->json('link.1.relation'));
        $this->getJson("/api/v1/fhir/AllergyIntolerance?patient={$patient->public_id}&_count=2&page=3", $headers)->assertJsonCount(1, 'entry');
        $this->getJson("/api/v1/fhir/AllergyIntolerance?patient={$patient->public_id}&bogus=1", $headers)->assertStatus(400)->assertJsonPath('issue.0.code', 'not-supported');
    }

    public function test_patient_search_only_finds_patients_who_agreed(): void
    {
        $partner = $this->partner();
        $headers = $this->fhirHeaders($partner);
        $yes = $this->patient(['last_name' => 'Rahman']);
        $no = $this->patient(['last_name' => 'Rahman']);
        $this->consent($yes);

        $res = $this->getJson('/api/v1/fhir/Patient?family=Rahman', $headers)->assertOk();
        $this->assertSame(1, $res->json('total'));
        $this->assertSame($yes->public_id, $res->json('entry.0.resource.id'));
        $this->assertNotContains($no->public_id, array_column(array_column($res->json('entry'), 'resource'), 'id'));
    }

    public function test_every_served_resource_type_maps_and_reads_back_by_its_own_id(): void
    {
        $partner = $this->partner(['requires_consent' => false]);
        $headers = $this->fhirHeaders($partner);
        $patient = $this->patient();
        $this->labReport($patient);
        $this->prescription($patient);
        $this->invoice($patient);
        PatientAllergy::create(['organization_id' => $this->organization->id, 'patient_id' => $patient->id, 'substance' => 'Penicillin', 'severity' => 'severe', 'is_active' => true]);
        $this->provider();

        $seen = [];
        $bundle = $this->getJson("/api/v1/fhir/Patient/{$patient->public_id}/\$everything", $headers)->assertOk();
        foreach ($bundle->json('entry') as $e) {
            $r = $e['resource'];
            $seen[$r['resourceType']] = true;
            $back = $this->getJson("/api/v1/fhir/{$r['resourceType']}/{$r['id']}", $headers);
            $this->assertSame(200, $back->getStatusCode(), "{$r['resourceType']}/{$r['id']}: ".$back->getContent());
            $this->assertSame($r['id'], $back->json('id'));
        }
        foreach (['Patient', 'Observation', 'DiagnosticReport', 'ServiceRequest', 'MedicationRequest', 'AllergyIntolerance', 'Encounter'] as $t) {
            $this->assertArrayHasKey($t, $seen, "$t is in the patient's bundle");
        }
    }

    public function test_a_token_without_write_scope_cannot_create_and_one_with_it_creates_once(): void
    {
        $partner = $this->partner(['requires_consent' => false]);
        $body = ['resourceType' => 'Patient', 'name' => [['family' => 'Chowdhury', 'given' => ['Nasrin']]], 'gender' => 'female', 'birthDate' => '1985-02-03', 'telecom' => [['system' => 'phone', 'value' => '01711111111']],
            'identifier' => [['system' => 'urn:bd:nid', 'value' => '1985123456789']]];

        $this->postJson('/api/v1/fhir/Patient', $body, $this->fhirHeaders($partner))->assertForbidden()->assertJsonPath('resourceType', 'OperationOutcome');
        $write = $this->fhirHeaders($partner, ['fhir.read', 'fhir.write']);
        $first = $this->postJson('/api/v1/fhir/Patient', $body, $write)->assertCreated();
        $again = $this->postJson('/api/v1/fhir/Patient', $body, $write)->assertOk();

        $this->assertSame($first->json('id'), $again->json('id'));
        $this->assertSame(1, Patient::query()->where('national_identifier', '1985123456789')->count());
        $this->assertSame('Nasrin', Patient::query()->where('national_identifier', '1985123456789')->value('first_name'));
    }

    public function test_a_bad_patient_is_refused_with_the_reasons(): void
    {
        $partner = $this->partner(['requires_consent' => false, 'profile' => 'bd-core']);
        $write = $this->fhirHeaders($partner, ['fhir.read', 'fhir.write']);

        $res = $this->postJson('/api/v1/fhir/Patient', ['resourceType' => 'Patient', 'name' => [['family' => 'X']]], $write)->assertStatus(422);
        $this->assertStringContainsString('Patient.birthDate is required by profile bd-core', $res->json('issue.0.diagnostics'));
        $this->postJson('/api/v1/fhir/Patient/$validate', ['resourceType' => 'Patient', 'gender' => 'robot'], $write)->assertStatus(422)->assertJsonPath('resourceType', 'OperationOutcome');
        $this->postJson('/api/v1/fhir/Patient/$validate', ['resourceType' => 'Patient', 'identifier' => [['value' => '1']], 'name' => [['family' => 'Y']], 'gender' => 'male', 'birthDate' => '1990-01-01'], $write)->assertOk()->assertJsonPath('issue.0.severity', 'information');
    }

    public function test_tokens_of_patients_and_nobody_cannot_use_the_fhir_api_and_staff_can_with_permission(): void
    {
        $this->getJson('/api/v1/fhir/Patient')->assertUnauthorized()->assertJsonPath('resourceType', 'OperationOutcome');
        $this->getJson('/api/v1/fhir/Patient', $this->bearer($this->account()))->assertUnauthorized();

        $patient = $this->patient();
        $staff = $this->actor(['interoperability.fhir.read']);
        $this->getJson("/api/v1/fhir/Patient/{$patient->public_id}", ['Authorization' => 'Bearer '.$staff->createToken('t', ['*'])->plainTextToken])->assertOk();
        $this->getJson("/api/v1/fhir/Patient/{$patient->public_id}", ['Authorization' => 'Bearer '.$this->actor([])->createToken('t', ['*'])->plainTextToken])->assertForbidden();
    }

    public function test_a_paused_partner_and_a_revoked_token_get_nothing(): void
    {
        $partner = $this->partner(['requires_consent' => false]);
        $headers = $this->fhirHeaders($partner);
        $patient = $this->patient();
        $this->getJson("/api/v1/fhir/Patient/{$patient->public_id}", $headers)->assertOk();

        $partner->update(['status' => 'paused']);
        $this->getJson("/api/v1/fhir/Patient/{$patient->public_id}", $headers)->assertForbidden();
        $partner->update(['status' => 'active']);
        app(CredentialService::class)->revokeTokens($partner);
        $this->getJson("/api/v1/fhir/Patient/{$patient->public_id}", $headers)->assertUnauthorized();
    }

    public function test_terminology_operations_read_the_code_registry(): void
    {
        $partner = $this->partner(['requires_consent' => false]);
        $headers = $this->fhirHeaders($partner);
        $icd = CodeSystem::create(['key' => 'icd10', 'uri' => 'http://hl7.org/fhir/sid/icd-10', 'name' => 'ICD-10']);
        $snomed = CodeSystem::create(['key' => 'snomed', 'uri' => 'http://snomed.info/sct', 'name' => 'SNOMED CT']);
        CodeConcept::create(['code_system_id' => $icd->id, 'code' => 'E11.9', 'display_en' => 'Type 2 diabetes mellitus without complications', 'display_bn' => 'টাইপ ২ ডায়াবেটিস']);
        CodeMap::create(['from_system_id' => $icd->id, 'from_code' => 'E11.9', 'to_system_id' => $snomed->id, 'to_code' => '44054006', 'relationship' => 'equivalent']);

        $lookup = $this->getJson('/api/v1/fhir/CodeSystem/$lookup?system=http://hl7.org/fhir/sid/icd-10&code=E11.9', $headers)->assertOk();
        $this->assertSame('Type 2 diabetes mellitus without complications', collect($lookup->json('parameter'))->firstWhere('name', 'display')['valueString']);
        $tr = $this->getJson('/api/v1/fhir/ConceptMap/$translate?system=icd10&code=E11.9&target=snomed', $headers)->assertOk();
        $this->assertTrue($tr->json('parameter.0.valueBoolean'));
        $this->assertSame('44054006', $tr->json('parameter.1.part.1.valueCoding.code'));
        $this->getJson('/api/v1/fhir/CodeSystem/$lookup?system=icd10&code=ZZZ', $headers)->assertNotFound();
    }
}
