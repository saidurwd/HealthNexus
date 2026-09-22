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

        Permission::create(['name' => 'encounters.create', 'guard_name' => 'web']);
        Permission::create(['name' => 'encounters.update', 'guard_name' => 'web']);
        Permission::create(['name' => 'encounters.delete', 'guard_name' => 'web']);
        $this->user->givePermissionTo('encounters.create');
        $this->user->givePermissionTo('encounters.update');
        $this->user->givePermissionTo('encounters.delete');

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
}
