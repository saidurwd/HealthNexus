<?php

namespace Tests\Feature\Api\V1;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EncounterApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $company;

    private Branch $branch;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->patient = Patient::factory()->create(['company_id' => $this->company->id]);

        $this->user->companies()->attach($this->company->id, ['access_level' => 'admin']);
        $this->user->branches()->attach($this->branch->id, ['access_level' => 'staff', 'company_id' => $this->company->id]);

        foreach ([
            'encounters.create', 'encounters.update', 'encounters.delete',
            'clinical.vitals.create', 'clinical.diagnosis.create', 'clinical.order.create',
            'prescription.create', 'prescription.issue', 'prescription.cancel', 'encounter.amend',
        ] as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
            $this->user->givePermissionTo($permission);
        }

        $token = $this->user->createToken('test-token')->plainTextToken;
        $this->withHeader('Authorization', 'Bearer '.$token);
        $this->withHeader('X-Company-Id', (string) $this->company->id);
        $this->withHeader('X-Branch-Id', (string) $this->branch->id);
    }

    public function test_user_can_list_encounters(): void
    {
        Encounter::factory()->count(3)->create(['company_id' => $this->company->id, 'branch_id' => $this->branch->id]);

        $response = $this->get('/api/v1/encounters');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_create_encounter(): void
    {
        $response = $this->post('/api/v1/encounters', [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $this->patient->id,
            'encounter_type' => 'OPD',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.encounter_type', 'OPD');
    }

    public function test_user_can_view_encounter(): void
    {
        $encounter = Encounter::factory()->create(['company_id' => $this->company->id, 'branch_id' => $this->branch->id]);

        $response = $this->get('/api/v1/encounters/'.$encounter->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $encounter->id);
    }

    public function test_user_can_update_encounter(): void
    {
        $encounter = Encounter::factory()->create(['company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'status' => 'in_progress']);

        $response = $this->put('/api/v1/encounters/'.$encounter->id, [
            'status' => 'completed',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'completed');
    }

    public function test_user_can_delete_encounter(): void
    {
        $encounter = Encounter::factory()->create(['company_id' => $this->company->id, 'branch_id' => $this->branch->id]);

        $response = $this->delete('/api/v1/encounters/'.$encounter->id);

        $response->assertStatus(200);
        $this->assertSoftDeleted('encounters', [
            'id' => $encounter->id,
        ]);
    }

    public function test_user_can_record_vital_signs_via_api(): void
    {
        $encounter = Encounter::factory()->create(['company_id' => $this->company->id, 'branch_id' => $this->branch->id]);

        $response = $this->post("/api/v1/encounters/{$encounter->id}/vitals", [
            'systolic' => 118,
            'diastolic' => 76,
            'pulse_rate' => 70,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.systolic', 118);

        $this->get("/api/v1/encounters/{$encounter->id}/vitals")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_user_can_add_diagnosis_via_api(): void
    {
        $encounter = Encounter::factory()->create(['company_id' => $this->company->id, 'branch_id' => $this->branch->id]);

        $response = $this->post("/api/v1/encounters/{$encounter->id}/diagnoses", [
            'description' => 'Acute bronchitis',
            'diagnosis_type' => 'primary',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.description', 'Acute bronchitis');
    }

    public function test_user_can_create_and_issue_prescription_via_api(): void
    {
        $encounter = Encounter::factory()->create(['company_id' => $this->company->id, 'branch_id' => $this->branch->id]);

        $response = $this->post("/api/v1/encounters/{$encounter->id}/prescriptions", [
            'items' => [
                ['medicine_name' => 'Amoxicillin', 'frequency' => 'TDS', 'duration' => '7 days'],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'draft');

        $prescriptionId = $response->json('data.id');

        $this->post("/api/v1/encounters/{$encounter->id}/prescriptions/{$prescriptionId}/issue")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'issued');
    }

    public function test_user_can_create_clinical_order_via_api(): void
    {
        $encounter = Encounter::factory()->create(['company_id' => $this->company->id, 'branch_id' => $this->branch->id]);

        $response = $this->post("/api/v1/encounters/{$encounter->id}/orders", [
            'order_type' => 'lab',
            'items' => [
                ['item_name' => 'CBC'],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.order_type', 'lab');
    }

    public function test_user_can_request_amendment_via_api(): void
    {
        $encounter = Encounter::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'status' => 'completed',
            'locked_at' => now(),
        ]);

        $response = $this->post("/api/v1/encounters/{$encounter->id}/amendments", [
            'amendment_type' => 'correction',
            'reason' => 'Corrected diagnosis code',
            'content' => 'Diagnosis code changed from I10 to I11.',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('encounter_amendments', [
            'encounter_id' => $encounter->id,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_a_different_user_can_approve_an_amendment_via_api(): void
    {
        $encounter = Encounter::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'status' => 'completed',
            'locked_at' => now(),
        ]);

        $amendment = app(\App\Services\Clinical\EncounterClinicalService::class)->createAmendment($encounter, [
            'amendment_type' => 'correction',
            'reason' => 'Corrected diagnosis code',
            'content' => 'Diagnosis code changed from I10 to I11.',
        ], $this->user);

        $approver = User::factory()->create();
        $approver->companies()->attach($this->company->id, ['access_level' => 'admin']);
        $approver->branches()->attach($this->branch->id, ['access_level' => 'manager', 'company_id' => $this->company->id]);
        $approver->givePermissionTo('encounter.amend');

        $token = $approver->createToken('approver-token')->plainTextToken;

        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('X-Company-Id', (string) $this->company->id)
            ->withHeader('X-Branch-Id', (string) $this->branch->id)
            ->put("/api/v1/encounters/{$encounter->id}/amendments/{$amendment->id}/approve")
            ->assertStatus(200)
            ->assertJsonPath('data.approved_by', $approver->id);
    }
}
