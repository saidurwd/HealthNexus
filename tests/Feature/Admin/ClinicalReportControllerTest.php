<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Diagnosis;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ClinicalReportControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $company;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);

        $this->user->companies()->attach($this->company->id, ['access_level' => 'admin']);
        $this->user->branches()->attach($this->branch->id, ['access_level' => 'manager', 'company_id' => $this->company->id]);

        Permission::create(['name' => 'encounter.export', 'guard_name' => 'web']);
        $this->user->givePermissionTo('encounter.export');

        $this->actingAs($this->user);

        session()->put('tenant_company_id', $this->company->id);
        app(\App\Services\TenantContextResolver::class)->setCompanyId($this->company->id);
    }

    public function test_clinical_report_index_renders(): void
    {
        Encounter::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'encounter_date' => now()->toDateString(),
        ]);

        $response = $this->get('/admin/reports/clinical');

        $response->assertStatus(200);
        $response->assertViewHas('stats');
    }

    public function test_daily_opd_report_renders(): void
    {
        $patient = Patient::factory()->create(['company_id' => $this->company->id]);

        Encounter::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $patient->id,
            'encounter_date' => now()->toDateString(),
        ]);

        $response = $this->get('/admin/reports/clinical/daily-opd');

        $response->assertStatus(200);
        $response->assertViewHas('encounters');
    }

    public function test_provider_workload_report_renders(): void
    {
        $encounter = Encounter::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'encounter_date' => now()->toDateString(),
        ]);

        $response = $this->get('/admin/reports/clinical/provider-workload');

        $response->assertStatus(200);
        $response->assertViewHas('workload');
        $this->assertNotNull($encounter->provider_id);
    }

    public function test_diagnosis_statistics_report_renders(): void
    {
        $encounter = Encounter::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'encounter_date' => now()->toDateString(),
        ]);

        Diagnosis::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'encounter_id' => $encounter->id,
            'patient_id' => $encounter->patient_id,
            'recorded_at' => now(),
        ]);

        $response = $this->get('/admin/reports/clinical/diagnosis-statistics');

        $response->assertStatus(200);
        $response->assertViewHas('diagnoses');
    }
}
