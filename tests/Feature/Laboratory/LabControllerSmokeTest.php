<?php

namespace Tests\Feature\Laboratory;

use App\Models\Department;
use App\Models\Encounter;
use App\Models\Laboratory\LabCriticalValue;
use App\Models\Laboratory\LabOrder;
use App\Models\Laboratory\LabQcMaterial;
use App\Models\Laboratory\LabReferenceRange;
use App\Models\Laboratory\LabSpecimen;
use App\Models\Laboratory\LabSpecimenType;
use App\Models\Laboratory\LabTest;
use App\Models\Patient;
use App\Models\User;
use App\Services\Laboratory\LabReportService;
use App\Services\Laboratory\ResultEntryService;
use App\Services\Laboratory\ResultValidationService;

/**
 * Exercises every GET view end-to-end against real seeded rows (not empty tables) — a blade
 * undefined-variable error or an ambiguous-column raw SQL bug (the exact class of bug the
 * equivalent Billing smoke test caught in RevenueService) only surfaces once real data flows
 * through the query/view, never against a plain "does the route exist" check.
 */
class LabControllerSmokeTest extends LabTestCase
{
    private LabOrder $order;

    protected function setUp(): void
    {
        parent::setUp();

        $specimenType = LabSpecimenType::create(['organization_id' => $this->organization->id, 'branch_id' => null, 'code' => 'BLOOD', 'name' => 'Blood', 'is_active' => true]);
        $test = LabTest::create([
            'organization_id' => $this->organization->id, 'branch_id' => null,
            'code' => 'HGB', 'name' => 'Hemoglobin', 'test_type' => 'quantitative', 'unit' => 'g/dL',
            'specimen_type_id' => $specimenType->id, 'is_active' => true,
        ]);

        LabReferenceRange::create(['test_id' => $test->id, 'gender' => 'any', 'unit' => 'g/dL', 'low' => '12.0', 'high' => '16.0', 'pregnancy_status' => 'any', 'is_active' => true]);
        LabCriticalValue::create(['test_id' => $test->id, 'low_threshold' => '5.0', 'high_threshold' => '20.0', 'is_active' => true]);

        $patient = Patient::factory()->create(['organization_id' => $this->organization->id]);
        $department = Department::create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'name' => 'OPD', 'code' => 'OPD-'.uniqid(), 'is_active' => true]);
        $encounter = Encounter::create([
            'organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id,
            'encounter_no' => 'ENC-'.uniqid(), 'encounter_type' => 'opd', 'provider_id' => $this->user->id,
            'department_id' => $department->id, 'encounter_date' => now()->toDateString(),
            'status' => 'in_progress', 'source' => 'walk_in', 'created_by' => $this->user->id,
        ]);

        $this->order = LabOrder::create([
            'organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'department_id' => $department->id,
            'patient_id' => $patient->id, 'encounter_id' => $encounter->id, 'order_number' => 'LAB-'.uniqid(),
            'priority' => 'routine', 'status' => 'received', 'ordered_by' => $this->user->id, 'ordered_at' => now(),
        ]);

        $specimen = LabSpecimen::create([
            'organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'lab_order_id' => $this->order->id,
            'specimen_type_id' => $specimenType->id, 'accession_number' => 'ACC-'.uniqid(), 'status' => 'received',
            'collected_by' => $this->user->id, 'collected_at' => now(), 'received_by' => $this->user->id, 'received_at' => now(),
        ]);

        $item = $this->order->items()->create([
            'test_id' => $test->id, 'specimen_id' => $specimen->id, 'requested_test_name' => 'Hemoglobin',
            'status' => 'received', 'result_status' => 'pending', 'requested_at' => now(),
        ]);

        $result = app(ResultEntryService::class)->enter($item, ['numeric_value' => '3.0'], $this->user);
        app(ResultValidationService::class)->technicalValidate($result, User::factory()->create());

        LabQcMaterial::create(['organization_id' => $this->organization->id, 'branch_id' => null, 'code' => 'QC1', 'name' => 'Level 1 Control', 'level' => 'low', 'is_active' => true]);

        app(LabReportService::class)->finalize($this->order->fresh(), $this->user);
    }

    public function test_dashboard_loads(): void
    {
        $this->get('/lab')->assertOk();
    }

    public function test_test_catalog_pages_load(): void
    {
        $this->get('/lab/tests')->assertOk();
        $this->get('/lab/tests/create')->assertOk();
        $this->get('/lab/panels')->assertOk();
        $this->get('/lab/panels/create')->assertOk();
    }

    public function test_order_and_specimen_pages_load(): void
    {
        $this->get('/lab/orders')->assertOk();
        $this->get('/lab/orders/'.$this->order->public_id)->assertOk();
        $this->get('/lab/specimens')->assertOk();
        $this->get('/lab/specimens/'.$this->order->specimens->first()->public_id)->assertOk();
        $this->get('/lab/specimens/'.$this->order->specimens->first()->public_id.'/label')->assertOk()->assertSee('Paper size: label 50', false)->assertSee('<svg', false);
    }

    public function test_worklist_and_critical_results_pages_load(): void
    {
        $this->get('/lab/worklist')->assertOk();
        $this->get('/lab/critical-results')->assertOk();
    }

    /**
     * The setUp() fixture's item is already past 'received'/'processing' by the time these
     * tests run, so a worklist hit with zero matching rows never actually executes the
     * ORDER BY clause (Eloquent's paginate() skips the item query entirely when count is 0) —
     * this is what let a MySQL-only FIELD() call sit undetected until a real row reached it.
     * A second, still-'received' item closes that gap.
     */
    public function test_worklist_renders_a_real_pending_item(): void
    {
        $this->order->items()->first()->update(['status' => 'received']);

        $this->get('/lab/worklist')->assertOk();
    }

    public function test_report_pages_load(): void
    {
        $report = $this->order->reports()->first();

        $this->get('/lab/reports')->assertOk();
        $this->get('/lab/reports/'.$report->public_id)->assertOk();
        $this->get('/lab/reports/'.$report->public_id.'/print')->assertOk();
        $this->get('/lab/reports-analytics/daily-volume')->assertOk();
        $this->get('/lab/reports-analytics/sample-rejection')->assertOk();
        $this->get('/lab/reports-analytics/critical-results')->assertOk();
        $this->get('/lab/reports-analytics/result-amendments')->assertOk();
    }

    public function test_analyzer_and_qc_pages_load(): void
    {
        $this->get('/lab/analyzers')->assertOk();
        $this->get('/lab/analyzers/create')->assertOk();
        $this->get('/lab/qc')->assertOk();
        $this->get('/lab/qc/create')->assertOk();
    }

    public function test_settings_page_loads(): void
    {
        $this->get('/lab/settings')->assertOk();
    }
}
