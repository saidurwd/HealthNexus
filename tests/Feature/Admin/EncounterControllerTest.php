<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EncounterControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    private Branch $branch;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->branch = Branch::factory()->create(['organization_id' => $this->organization->id]);
        $this->patient = Patient::factory()->create(['organization_id' => $this->organization->id]);

        $this->user->organizations()->attach($this->organization->id, ['access_level' => 'admin']);
        $this->user->branches()->attach($this->branch->id, ['access_level' => 'manager', 'organization_id' => $this->organization->id]);

        Permission::create(['name' => 'manage organizations', 'guard_name' => 'web']);
        Permission::create(['name' => 'encounters.view', 'guard_name' => 'web']);
        Permission::create(['name' => 'encounters.create', 'guard_name' => 'web']);
        Permission::create(['name' => 'encounters.update', 'guard_name' => 'web']);
        Permission::create(['name' => 'encounters.delete', 'guard_name' => 'web']);
        $this->user->givePermissionTo('manage organizations');
        $this->user->givePermissionTo('encounters.view');
        $this->user->givePermissionTo('encounters.create');
        $this->user->givePermissionTo('encounters.update');
        $this->user->givePermissionTo('encounters.delete');

        $this->actingAs($this->user);

        session()->put('tenant_organization_id', $this->organization->id);
        app(\App\Services\TenantContextResolver::class)->setOrganizationId($this->organization->id);
    }

    public function test_user_can_view_encounters_index(): void
    {
        Encounter::factory()->count(3)->create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id]);

        $response = $this->get('/admin/encounters');

        $response->assertStatus(200);
        $response->assertViewHas('encounters');
    }

    public function test_user_can_view_encounter_create_page(): void
    {
        $response = $this->get('/admin/encounters/create');

        $response->assertStatus(200);
        $response->assertViewIs('admin.encounters.create');
    }

    public function test_user_can_create_encounter(): void
    {
        $response = $this->post('/admin/encounters', [
            'branch_id' => $this->branch->id,
            'patient_id' => $this->patient->id,
            'encounter_type' => 'OPD',
        ]);

        $this->assertDatabaseHas('encounters', [
            'patient_id' => $this->patient->id,
            'encounter_type' => 'OPD',
        ]);

        $encounter = Encounter::where('patient_id', $this->patient->id)->firstOrFail();
        $response->assertRedirect('/admin/encounters/'.$encounter->public_id);
    }

    public function test_user_can_view_encounter(): void
    {
        $encounter = Encounter::factory()->create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id]);

        $response = $this->get('/admin/encounters/'.$encounter->public_id);

        $response->assertStatus(200);
        $response->assertViewHas('encounter');
    }

    public function test_user_can_update_encounter(): void
    {
        $encounter = Encounter::factory()->create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'status' => 'in_progress']);

        $response = $this->put('/admin/encounters/'.$encounter->public_id, [
            'status' => 'completed',
        ]);

        $response->assertRedirect('/admin/encounters/'.$encounter->public_id);
        $this->assertDatabaseHas('encounters', [
            'id' => $encounter->id,
            'status' => 'completed',
        ]);
    }

    public function test_user_can_delete_encounter(): void
    {
        $encounter = Encounter::factory()->create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id]);

        $response = $this->delete('/admin/encounters/'.$encounter->public_id);

        $response->assertRedirect('/admin/encounters');
        $this->assertSoftDeleted('encounters', [
            'id' => $encounter->id,
        ]);
    }
}
