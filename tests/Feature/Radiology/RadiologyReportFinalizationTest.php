<?php

namespace Tests\Feature\Radiology;

use App\Models\Department;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\Radiology\RadiologyModality;
use App\Models\Radiology\RadiologyOrder;
use App\Models\Radiology\RadiologyProcedure;
use App\Models\User;
use App\Services\Radiology\RadiologyExaminationService;
use App\Services\Radiology\RadiologyReportService;
use App\Services\Radiology\RadiologySchedulingService;
use Carbon\Carbon;

class RadiologyReportFinalizationTest extends RadiologyTestCase
{
    public function test_finalizing_a_report_records_a_patient_timeline_event(): void
    {
        $procedure = RadiologyProcedure::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'code' => 'XR-CHEST', 'name' => 'Chest X-Ray', 'modality_type' => 'XR', 'is_active' => true,
        ]);

        $modality = RadiologyModality::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
            'code' => 'XR-01', 'name' => 'X-Ray Room 1', 'modality_type' => 'XR', 'status' => 'online', 'is_active' => true,
        ]);

        $patient = Patient::factory()->create(['company_id' => $this->company->id]);
        $department = Department::create(['company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'name' => 'Radiology', 'code' => 'RAD-'.uniqid(), 'is_active' => true]);
        $encounter = Encounter::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id,
            'encounter_no' => 'ENC-'.uniqid(), 'encounter_type' => 'opd', 'provider_id' => $this->user->id,
            'department_id' => $department->id, 'encounter_date' => now()->toDateString(),
            'status' => 'in_progress', 'source' => 'walk_in', 'created_by' => $this->user->id,
        ]);

        $order = RadiologyOrder::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'department_id' => $department->id,
            'patient_id' => $patient->id, 'encounter_id' => $encounter->id, 'order_number' => 'RAD-'.uniqid(),
            'accession_number' => 'RAD-ACC-'.uniqid(), 'priority' => 'routine', 'status' => 'registered',
            'ordered_by' => $this->user->id, 'ordered_at' => now(),
        ]);

        $item = $order->items()->create([
            'procedure_id' => $procedure->id, 'requested_procedure_name' => 'Chest X-Ray',
            'status' => 'pending', 'result_status' => 'pending', 'requested_at' => now(),
        ]);

        $technologistUser = User::factory()->create();
        $technologist = Provider::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'user_id' => $technologistUser->id,
            'provider_code' => 'TECH-'.uniqid(), 'name' => $technologistUser->name, 'provider_type' => 'radiology_technician', 'status' => 'active',
        ]);

        $examination = app(RadiologySchedulingService::class)->schedule($item, $modality, $technologist, Carbon::parse('2026-10-01 09:00:00'), $this->user);

        $examinationService = app(RadiologyExaminationService::class);
        $examination = $examinationService->checkIn($examination, $this->user);
        $examination = $examinationService->start($examination, $this->user);
        $examination = $examinationService->complete($examination, $this->user);

        $radiologistUser = User::factory()->create();
        $radiologist = Provider::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'user_id' => $radiologistUser->id,
            'provider_code' => 'RAD-'.uniqid(), 'name' => $radiologistUser->name, 'provider_type' => 'radiologist', 'status' => 'active',
        ]);

        $report = app(RadiologyReportService::class)->createDraft($examination, $radiologist, ['findings' => 'Clear.', 'impression' => 'Normal.']);
        $report = app(RadiologyReportService::class)->submit($report);

        $approverUser = User::factory()->create();
        $approver = Provider::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'user_id' => $approverUser->id,
            'provider_code' => 'RAD-2-'.uniqid(), 'name' => $approverUser->name, 'provider_type' => 'senior_radiologist', 'status' => 'active',
        ]);

        $report = app(RadiologyReportService::class)->approve($report, $approver);

        $this->assertDatabaseHas('patient_timeline_events', [
            'patient_id' => $patient->id,
            'event_type' => 'RADIOLOGY_REPORT_FINALIZED',
            'subject_id' => $report->id,
        ]);
    }
}
