<?php

namespace Tests\Feature\Interop;

use App\Models\Department;
use App\Models\Encounter;
use App\Models\Integration\IntegrationPartner;
use App\Models\Patient;
use App\Models\PatientConsent;
use App\Models\Provider;
use App\Models\Radiology\RadiologyExamination;
use App\Models\Radiology\RadiologyModality;
use App\Models\Radiology\RadiologyOrder;
use App\Models\Radiology\RadiologyProcedure;
use App\Models\User;
use App\Services\Interop\CredentialService;
use App\Services\Radiology\RadiologySchedulingService;
use Tests\Feature\Portal\PortalTestCase;

/** Same hospital, branch and record fixtures as the portal tests, plus outside partners with tokens and patient consent. */
abstract class InteropTestCase extends PortalTestCase
{
    protected function partner(array $o = []): IntegrationPartner
    {
        return IntegrationPartner::factory()->create(array_merge(['organization_id' => $this->organization->id], $o));
    }

    /** @return array<string, string> headers for a partner's client token */
    protected function fhirHeaders(IntegrationPartner $partner, array $abilities = ['fhir.read']): array
    {
        return ['Authorization' => 'Bearer '.app(CredentialService::class)->issueToken($partner, $abilities), 'Accept' => 'application/fhir+json'];
    }

    protected function consent(Patient $patient, string $type = 'data_sharing'): void
    {
        PatientConsent::query()->create(['organization_id' => $patient->organization_id, 'patient_id' => $patient->id, 'consent_type' => $type, 'version' => 1, 'status' => 'active', 'granted_at' => now()]);
    }

    /** A scheduled radiology exam on a modality with the given AE title. */
    protected function radiologyExam(Patient $patient, string $type = 'XR', string $ae = 'XR01', string $when = '+1 day 09:00', string $procedure = 'Chest X-Ray'): RadiologyExamination
    {
        $user = $this->superAdmin();
        $procedureRow = RadiologyProcedure::query()->firstOrCreate(['organization_id' => $this->organization->id, 'code' => 'P-'.$type], ['name' => $procedure, 'modality_type' => $type, 'is_active' => true]);
        $modality = RadiologyModality::query()->firstOrCreate(['organization_id' => $this->organization->id, 'code' => 'M-'.$ae], ['branch_id' => $this->branch->id, 'name' => "Room $ae", 'modality_type' => $type, 'ae_title' => $ae, 'status' => 'online', 'is_active' => true]);
        $dept = Department::query()->where('organization_id', $this->organization->id)->first() ?? Department::create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'name' => 'Radiology', 'code' => 'RAD-'.uniqid(), 'is_active' => true]);
        $enc = Encounter::create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id, 'encounter_no' => 'ENC-'.uniqid(), 'encounter_type' => 'opd', 'provider_id' => $user->id,
            'department_id' => $dept->id, 'encounter_date' => now()->toDateString(), 'status' => 'in_progress', 'source' => 'walk_in', 'created_by' => $user->id]);
        $order = RadiologyOrder::create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'department_id' => $dept->id, 'patient_id' => $patient->id, 'encounter_id' => $enc->id,
            'order_number' => 'RAD-'.uniqid(), 'accession_number' => 'ACC-'.strtoupper(uniqid()), 'priority' => 'routine', 'status' => 'registered', 'ordered_by' => $user->id, 'ordered_at' => now()]);
        $item = $order->items()->create(['procedure_id' => $procedureRow->id, 'requested_procedure_name' => $procedure, 'status' => 'pending', 'result_status' => 'pending', 'requested_at' => now()]);
        $techUser = User::factory()->create();
        $tech = Provider::create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'user_id' => $techUser->id, 'provider_code' => 'T-'.uniqid(), 'name' => $techUser->name, 'provider_type' => 'radiology_technician', 'status' => 'active']);

        return app(RadiologySchedulingService::class)->schedule($item, $modality, $tech, now()->modify($when), $user);
    }
}
