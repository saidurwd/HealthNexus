<?php

namespace Tests\Feature\Clinical;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\PatientAllergy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EncounterClinicalControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $company;

    private Branch $branch;

    private Patient $patient;

    private Encounter $encounter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->patient = Patient::factory()->create(['company_id' => $this->company->id]);

        $this->user->companies()->attach($this->company->id, ['access_level' => 'admin']);
        $this->user->branches()->attach($this->branch->id, ['access_level' => 'manager', 'company_id' => $this->company->id]);

        foreach (['encounters.view', 'clinical.vitals.create', 'clinical.diagnosis.create', 'prescription.create'] as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
            $this->user->givePermissionTo($permission);
        }

        $this->actingAs($this->user);

        session()->put('tenant_company_id', $this->company->id);
        app(\App\Services\TenantContextResolver::class)->setCompanyId($this->company->id);

        $this->encounter = Encounter::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $this->patient->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_user_can_record_vital_signs(): void
    {
        $response = $this->post("/admin/encounters/{$this->encounter->id}/vitals", [
            'temperature' => 37.2,
            'systolic' => 120,
            'diastolic' => 80,
            'pulse_rate' => 72,
            'respiratory_rate' => 16,
            'oxygen_saturation' => 98,
            'height' => 170,
            'weight' => 70,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('vital_signs', [
            'encounter_id' => $this->encounter->id,
            'systolic' => 120,
            'diastolic' => 80,
        ]);
    }

    public function test_user_can_add_diagnosis(): void
    {
        $response = $this->post("/admin/encounters/{$this->encounter->id}/diagnoses", [
            'description' => 'Essential hypertension',
            'coding_system' => 'ICD-10',
            'code' => 'I10',
            'diagnosis_type' => 'primary',
            'is_primary' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('diagnoses', [
            'encounter_id' => $this->encounter->id,
            'description' => 'Essential hypertension',
            'is_primary' => true,
        ]);
    }

    public function test_user_can_create_prescription(): void
    {
        $response = $this->post("/admin/encounters/{$this->encounter->id}/prescriptions", [
            'clinical_notes' => 'Take after meals',
            'items' => [
                ['medicine_name' => 'Paracetamol', 'frequency' => 'TDS', 'duration' => '5 days', 'quantity' => 15],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('prescriptions', [
            'encounter_id' => $this->encounter->id,
            'status' => 'draft',
        ]);
        $this->assertDatabaseHas('prescription_items', [
            'medicine_name' => 'Paracetamol',
        ]);
    }

    public function test_clinical_workspace_renders_with_full_sub_record_set_and_allergy_alert(): void
    {
        PatientAllergy::factory()->create([
            'company_id' => $this->company->id,
            'patient_id' => $this->patient->id,
            'substance' => 'Penicillin',
            'severity' => 'severe',
            'is_active' => true,
        ]);

        $this->post("/admin/encounters/{$this->encounter->id}/vitals", [
            'temperature' => 37.2,
            'systolic' => 120,
            'diastolic' => 80,
            'pulse_rate' => 72,
            'oxygen_saturation' => 98,
        ]);

        $this->post("/admin/encounters/{$this->encounter->id}/diagnoses", [
            'description' => 'Essential hypertension',
        ]);

        $this->post("/admin/encounters/{$this->encounter->id}/prescriptions", [
            'items' => [
                ['medicine_name' => 'Paracetamol', 'frequency' => 'TDS', 'duration' => '5 days'],
            ],
        ]);

        $response = $this->get("/admin/encounters/{$this->encounter->id}");

        $response->assertStatus(200);
        $response->assertSee('Penicillin');
        $response->assertSee('Essential hypertension');
        $response->assertSee('Paracetamol');
        $response->assertSee('120');
    }
}
