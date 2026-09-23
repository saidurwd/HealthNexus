<?php

namespace Tests\Feature\Radiology;

use App\Models\Department;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\Radiology\RadiologyModality;
use App\Models\Radiology\RadiologyOrder;
use App\Models\Radiology\RadiologyPacsServer;
use App\Models\Radiology\RadiologyProcedure;
use App\Models\User;
use App\Services\Radiology\RadiologyExaminationService;
use App\Services\Radiology\RadiologyReportService;
use App\Services\Radiology\RadiologySchedulingService;
use Carbon\Carbon;

/**
 * Exercises every GET view end-to-end against real seeded rows — the same technique that
 * caught a real bug in Laboratory's SpecimenService transition graph.
 */
class RadiologyControllerSmokeTest extends RadiologyTestCase
{
    private RadiologyOrder $order;

    private \App\Models\Radiology\RadiologyExamination $examination;

    private \App\Models\Radiology\RadiologyReport $report;

    protected function setUp(): void
    {
        parent::setUp();

        $procedure = RadiologyProcedure::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'code' => 'CT-BRAIN', 'name' => 'CT Brain', 'modality_type' => 'CT', 'is_active' => true,
        ]);

        $modality = RadiologyModality::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
            'code' => 'CT-01', 'name' => 'CT Scanner 1', 'modality_type' => 'CT', 'status' => 'online', 'is_active' => true,
        ]);

        $patient = Patient::factory()->create(['company_id' => $this->company->id]);
        $department = Department::create(['company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'name' => 'Radiology', 'code' => 'RAD-'.uniqid(), 'is_active' => true]);
        $encounter = Encounter::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id,
            'encounter_no' => 'ENC-'.uniqid(), 'encounter_type' => 'opd', 'provider_id' => $this->user->id,
            'department_id' => $department->id, 'encounter_date' => now()->toDateString(),
            'status' => 'in_progress', 'source' => 'walk_in', 'created_by' => $this->user->id,
        ]);

        $this->order = RadiologyOrder::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'department_id' => $department->id,
            'patient_id' => $patient->id, 'encounter_id' => $encounter->id, 'order_number' => 'RAD-'.uniqid(),
            'accession_number' => 'RAD-ACC-'.uniqid(), 'priority' => 'routine', 'status' => 'registered',
            'ordered_by' => $this->user->id, 'ordered_at' => now(),
        ]);

        $item = $this->order->items()->create([
            'procedure_id' => $procedure->id, 'requested_procedure_name' => 'CT Brain',
            'status' => 'pending', 'result_status' => 'pending', 'requested_at' => now(),
        ]);

        $technologistUser = User::factory()->create();
        $technologist = Provider::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'user_id' => $technologistUser->id,
            'provider_code' => 'TECH-'.uniqid(), 'name' => $technologistUser->name, 'provider_type' => 'radiology_technician', 'status' => 'active',
        ]);

        $this->examination = app(RadiologySchedulingService::class)->schedule($item, $modality, $technologist, Carbon::parse('2026-10-01 09:00:00'), $this->user);

        $examinationService = app(RadiologyExaminationService::class);
        $this->examination = $examinationService->checkIn($this->examination, $this->user);
        $this->examination = $examinationService->start($this->examination, $this->user);
        $this->examination = $examinationService->complete($this->examination, $this->user);

        $radiologistUser = User::factory()->create();
        $radiologist = Provider::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'user_id' => $radiologistUser->id,
            'provider_code' => 'RAD-'.uniqid(), 'name' => $radiologistUser->name, 'provider_type' => 'radiologist', 'status' => 'active',
        ]);

        $this->report = app(RadiologyReportService::class)->createDraft($this->examination, $radiologist, ['findings' => 'Clear.', 'impression' => 'Normal.']);

        RadiologyPacsServer::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'code' => 'DEFAULT', 'name' => 'Default PACS', 'adapter_type' => 'null', 'is_active' => false, 'is_default' => true,
        ]);
    }

    public function test_dashboard_loads(): void
    {
        $this->get('/admin/radiology')->assertOk();
    }

    public function test_catalog_pages_load(): void
    {
        $this->get('/admin/radiology/procedures')->assertOk();
        $this->get('/admin/radiology/procedures/create')->assertOk();
        $this->get('/admin/radiology/modalities')->assertOk();
        $this->get('/admin/radiology/modalities/create')->assertOk();
    }

    public function test_order_and_scheduling_pages_load(): void
    {
        $this->get('/admin/radiology/orders')->assertOk();
        $this->get('/admin/radiology/orders/'.$this->order->id)->assertOk();
    }

    public function test_examination_and_worklist_pages_load(): void
    {
        $this->get('/admin/radiology/examinations')->assertOk();
        $this->get('/admin/radiology/examinations/'.$this->examination->id)->assertOk();
        $this->get('/admin/radiology/worklist')->assertOk();
    }

    public function test_study_pages_load(): void
    {
        $this->get('/admin/radiology/studies')->assertOk();
    }

    public function test_report_pages_load(): void
    {
        $this->get('/admin/radiology/reports')->assertOk();
        $this->get('/admin/radiology/reports/'.$this->report->id)->assertOk();
    }

    public function test_critical_findings_page_loads(): void
    {
        $this->get('/admin/radiology/critical-findings')->assertOk();
    }

    public function test_analytics_pages_load(): void
    {
        $this->get('/admin/radiology/analytics/daily-volume')->assertOk();
        $this->get('/admin/radiology/analytics/modality-utilization')->assertOk();
        $this->get('/admin/radiology/analytics/turnaround-time')->assertOk();
        $this->get('/admin/radiology/analytics/critical-findings')->assertOk();
    }

    public function test_pacs_pages_load(): void
    {
        $this->get('/admin/radiology/pacs')->assertOk();
        $this->get('/admin/radiology/pacs/create')->assertOk();
    }

    public function test_settings_page_loads(): void
    {
        $this->get('/admin/radiology/settings')->assertOk();
    }
}
