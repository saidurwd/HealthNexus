<?php

namespace Tests\Feature\Interop;

use App\Models\Integration\DicomWorklistEntry;
use App\Models\Radiology\RadiologyStudy;
use App\Services\Interop\DicomWorklistService;

class DicomTest extends InteropTestCase
{
    private function scanner(array $abilities = ['dicom.read']): array
    {
        return $this->fhirHeaders($this->partner(['kind' => 'dicom_node', 'requires_consent' => false, 'ae_title' => 'XR01']), $abilities);
    }

    public function test_a_radiology_order_becomes_a_worklist_entry_the_scanner_can_ask_for(): void
    {
        $patient = $this->patient(['enterprise_patient_no' => 'EPT-000001', 'first_name' => 'Rina', 'middle_name' => 'Begum', 'last_name' => 'Akter', 'sex' => 'F', 'date_of_birth' => '1990-04-12']);
        $exam = $this->radiologyExam($patient, 'XR', 'XR01');

        $this->assertSame(['created' => 1, 'updated' => 0], app(DicomWorklistService::class)->sync($this->organization->id));
        $this->assertSame(['created' => 0, 'updated' => 0], app(DicomWorklistService::class)->sync($this->organization->id), 'a second sync adds nothing');
        $entry = DicomWorklistEntry::query()->first();
        $this->assertSame('Akter^Rina^Begum', $entry->patient_name);
        $this->assertSame('EPT-000001', $entry->patient_mrn);
        $this->assertSame('CR', $entry->modality);
        $this->assertSame('XR01', $entry->station_ae_title);
        $this->assertStringStartsWith('ACC-', $entry->accession_number);
        $this->assertSame($exam->id, $entry->examination_id);

        $res = $this->getJson('/api/v1/dicom/worklist?modality=CR&ae=XR01', $this->scanner())->assertOk()->assertHeader('Content-Type', 'application/dicom+json');
        $item = $res->json('0');
        $this->assertSame($entry->accession_number, $item['00080050']['Value'][0]);
        $this->assertSame('Akter^Rina^Begum', $item['00100010']['Value'][0]['Alphabetic']);
        $this->assertSame('19900412', $item['00100030']['Value'][0]);
        $this->assertSame('F', $item['00100040']['Value'][0]);
        $step = $item['00400100']['Value'][0];
        $this->assertSame('CR', $step['00080060']['Value'][0]);
        $this->assertSame('XR01', $step['00400001']['Value'][0]);
        $this->assertSame('SCHEDULED', $step['00400020']['Value'][0]);
    }

    public function test_the_worklist_can_be_narrowed_and_a_scanner_with_no_scope_is_refused(): void
    {
        $this->radiologyExam($this->patient(), 'XR', 'XR01', '+1 day 09:00');
        $this->radiologyExam($this->patient(), 'CT', 'CT01', '+1 day 10:00', 'CT head');
        app(DicomWorklistService::class)->sync($this->organization->id);
        $h = $this->scanner();

        $this->assertCount(2, $this->getJson('/api/v1/dicom/worklist', $h)->json());
        $this->assertCount(1, $this->getJson('/api/v1/dicom/worklist?modality=CT', $h)->json());
        $this->assertCount(1, $this->getJson('/api/v1/dicom/worklist?ae=XR01', $h)->json());
        $this->assertCount(0, $this->getJson('/api/v1/dicom/worklist?modality=MR', $h)->json());
        $this->getJson('/api/v1/dicom/worklist', $this->fhirHeaders($this->partner(), ['fhir.read']))->assertForbidden();
        $this->getJson('/api/v1/dicom/worklist')->assertUnauthorized();
    }

    public function test_the_scanner_reports_progress_and_a_finished_exam_leaves_the_list(): void
    {
        $this->radiologyExam($this->patient(), 'XR', 'XR01');
        app(DicomWorklistService::class)->sync($this->organization->id);
        $acc = DicomWorklistEntry::query()->value('accession_number');
        $h = $this->scanner(['dicom.read', 'dicom.write']);

        $this->putJson("/api/v1/dicom/worklist/$acc/status", ['status' => 'in_progress'], $h)->assertOk();
        $this->assertCount(0, $this->getJson('/api/v1/dicom/worklist', $h)->json(), 'only scheduled entries are offered');
        $this->putJson("/api/v1/dicom/worklist/$acc/status", ['status' => 'completed'], $h)->assertOk();
        $this->putJson("/api/v1/dicom/worklist/$acc/status", ['status' => 'cancelled'], $h)->assertStatus(409);
        $this->putJson('/api/v1/dicom/worklist/NOPE/status', ['status' => 'completed'], $h)->assertNotFound();
        $this->putJson("/api/v1/dicom/worklist/$acc/status", ['status' => 'completed'], $this->scanner())->assertForbidden();
    }

    public function test_the_entry_follows_the_exam_when_it_is_started_or_cancelled(): void
    {
        $a = $this->radiologyExam($this->patient(), 'XR', 'XR01', '+1 day 09:00');
        $b = $this->radiologyExam($this->patient(), 'XR', 'XR01', '+1 day 10:00');
        $svc = app(DicomWorklistService::class);
        $svc->sync($this->organization->id);

        $a->update(['status' => 'in_progress']);
        $b->update(['status' => 'cancelled']);
        $this->assertSame(['created' => 0, 'updated' => 2], $svc->sync($this->organization->id));
        $this->assertSame(['in_progress', 'cancelled'], DicomWorklistEntry::query()->orderBy('examination_id')->pluck('status')->all());
    }

    public function test_study_lookup_honours_patient_consent(): void
    {
        $with = $this->patient(['enterprise_patient_no' => 'EPT-000001', 'last_name' => 'Akter', 'first_name' => 'Rina']);
        $without = $this->patient(['enterprise_patient_no' => 'EPT-000002']);
        $this->consent($with);
        foreach ([$with, $without] as $i => $p) {
            $exam = $this->radiologyExam($p, 'XR', 'XR01', '+1 day '.(9 + $i).':00');
            RadiologyStudy::create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'radiology_examination_id' => $exam->id, 'patient_id' => $p->id, 'accession_number' => 'ACC-'.$p->id,
                'study_instance_uid' => '1.2.826.0.1.'.$p->id, 'study_date' => now()->toDateString(), 'modality' => 'CR', 'study_description' => 'Chest', 'number_of_series' => 1, 'number_of_instances' => 2, 'pacs_status' => 'available', 'study_status' => 'available']);
        }
        $consenting = $this->fhirHeaders($this->partner(['kind' => 'dicom_node']), ['dicom.read']);

        $this->assertCount(1, $this->getJson('/api/v1/dicom/studies?PatientID=EPT-000001', $consenting)->json());
        $this->assertCount(0, $this->getJson('/api/v1/dicom/studies?PatientID=EPT-000002', $consenting)->json(), 'the patient has not agreed: the study is not in the answer');
        $this->getJson('/api/v1/dicom/studies', $consenting)->assertStatus(400);
        $study = $this->getJson('/api/v1/dicom/studies?PatientID=EPT-000001', $consenting)->json('0');
        $this->assertSame('1.2.826.0.1.'.$with->id, $study['0020000D']['Value'][0]);
        $this->assertSame(2, $study['00201208']['Value'][0]);
    }
}
