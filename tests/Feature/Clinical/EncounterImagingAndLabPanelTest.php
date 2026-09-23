<?php

namespace Tests\Feature\Clinical;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Encounter;
use App\Models\Laboratory\LabOrder;
use App\Models\Laboratory\LabSpecimenType;
use App\Models\Laboratory\LabTest;
use App\Models\Patient;
use App\Models\Radiology\RadiologyModality;
use App\Models\Radiology\RadiologyOrder;
use App\Models\Radiology\RadiologyProcedure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Proves the Encounter clinical workspace's "Lab Results" and "Imaging" tabs (spec-promised
 * EMR integration for Phase 5/6) actually render against real Lab/Radiology data attached to
 * the same encounter — the same "hit it with real rows" technique that caught the Lab
 * WorklistService FIELD() bug and the Radiology ambiguous-column bugs.
 */
class EncounterImagingAndLabPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_encounter_workspace_renders_lab_results_and_imaging_tabs_with_real_data(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $patient = Patient::factory()->create(['company_id' => $company->id]);
        $department = Department::create(['company_id' => $company->id, 'branch_id' => $branch->id, 'name' => 'OPD', 'code' => 'OPD-'.uniqid(), 'is_active' => true]);

        $user->companies()->attach($company->id, ['access_level' => 'admin']);
        $user->branches()->attach($branch->id, ['access_level' => 'manager', 'company_id' => $company->id]);

        foreach (['encounters.view', 'lab.report.view', 'radiology.report.view', 'radiology.study.view'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $user->givePermissionTo($permission);
        }

        $this->actingAs($user);
        session()->put('tenant_company_id', $company->id);
        app(\App\Services\TenantContextResolver::class)->setCompanyId($company->id);

        $encounter = Encounter::factory()->create([
            'company_id' => $company->id, 'branch_id' => $branch->id, 'patient_id' => $patient->id, 'status' => 'in_progress',
        ]);

        // Lab order attached to this encounter.
        $labSpecimenType = LabSpecimenType::create(['company_id' => $company->id, 'branch_id' => null, 'code' => 'BLOOD', 'name' => 'Blood', 'is_active' => true]);
        $labTest = LabTest::create([
            'company_id' => $company->id, 'branch_id' => null, 'code' => 'HGB', 'name' => 'Hemoglobin',
            'test_type' => 'quantitative', 'unit' => 'g/dL', 'specimen_type_id' => $labSpecimenType->id, 'is_active' => true,
        ]);
        $labOrder = LabOrder::create([
            'company_id' => $company->id, 'branch_id' => $branch->id, 'department_id' => $department->id,
            'patient_id' => $patient->id, 'encounter_id' => $encounter->id, 'order_number' => 'LAB-'.uniqid(),
            'priority' => 'routine', 'status' => 'registered', 'ordered_by' => $user->id, 'ordered_at' => now(),
        ]);
        $labOrder->items()->create([
            'test_id' => $labTest->id, 'requested_test_name' => 'Hemoglobin', 'status' => 'pending', 'result_status' => 'pending', 'requested_at' => now(),
        ]);

        // Radiology order attached to this encounter.
        $procedure = RadiologyProcedure::create([
            'company_id' => $company->id, 'branch_id' => null, 'code' => 'XR-CHEST', 'name' => 'Chest X-Ray', 'modality_type' => 'XR', 'is_active' => true,
        ]);
        RadiologyModality::create([
            'company_id' => $company->id, 'branch_id' => $branch->id, 'code' => 'XR-01', 'name' => 'X-Ray Room 1', 'modality_type' => 'XR', 'status' => 'online', 'is_active' => true,
        ]);
        $radiologyOrder = RadiologyOrder::create([
            'company_id' => $company->id, 'branch_id' => $branch->id, 'department_id' => $department->id,
            'patient_id' => $patient->id, 'encounter_id' => $encounter->id, 'order_number' => 'RAD-'.uniqid(),
            'accession_number' => 'RAD-ACC-'.uniqid(), 'priority' => 'routine', 'status' => 'registered', 'ordered_by' => $user->id, 'ordered_at' => now(),
        ]);
        $radiologyOrder->items()->create([
            'procedure_id' => $procedure->id, 'requested_procedure_name' => 'Chest X-Ray', 'status' => 'pending', 'result_status' => 'pending', 'requested_at' => now(),
        ]);

        $response = $this->get('/admin/encounters/'.$encounter->id);

        $response->assertOk();
        $response->assertSee('Lab Results');
        $response->assertSee($labOrder->order_number);
        $response->assertSee('Imaging');
        $response->assertSee($radiologyOrder->accession_number);
    }
}
