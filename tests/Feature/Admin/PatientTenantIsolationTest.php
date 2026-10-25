<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\PatientDocument;
use App\Models\User;
use App\Services\TenantContextResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * PatientPolicy::update()/delete() previously checked only the Spatie permission string, with no
 * check that the target patient belongs to an organization the user is actually assigned to — a user
 * with patients.update could update another organization's patient if they could reach the route.
 * These tests guard against that regression.
 */
class PatientTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $ownOrganization;

    private Organization $otherOrganization;

    private Patient $otherOrganizationPatient;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->ownOrganization = Organization::factory()->create();
        $branch = Branch::factory()->create(['organization_id' => $this->ownOrganization->id]);
        $this->otherOrganization = Organization::factory()->create();

        $this->user = User::factory()->create();
        $this->user->organizations()->attach($this->ownOrganization->id, ['access_level' => 'admin']);
        $this->user->branches()->attach($branch->id, ['access_level' => 'manager', 'organization_id' => $this->ownOrganization->id]);

        foreach (['manage organizations', 'patients.view', 'patients.create', 'patients.update', 'patients.delete', 'patients.merge', 'patients.documents.view', 'patients.documents.manage'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $this->user->givePermissionTo($permission);
        }

        $this->actingAs($this->user);
        session()->put('tenant_organization_id', $this->ownOrganization->id);
        app(TenantContextResolver::class)->setOrganizationId($this->ownOrganization->id);

        $this->otherOrganizationPatient = Patient::factory()->create(['organization_id' => $this->otherOrganization->id]);
    }

    public function test_cannot_update_another_organizations_patient_despite_having_permission(): void
    {
        $response = $this->put('/admin/patients/'.$this->otherOrganizationPatient->public_id, [
            'organization_id' => $this->otherOrganization->id,
            'first_name' => 'Hacked',
            'last_name' => 'Name',
            'status' => 'active',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('patients', ['id' => $this->otherOrganizationPatient->id, 'first_name' => 'Hacked']);
    }

    public function test_cannot_delete_another_organizations_patient_despite_having_permission(): void
    {
        $response = $this->delete('/admin/patients/'.$this->otherOrganizationPatient->public_id);

        $response->assertForbidden();
        $this->assertDatabaseHas('patients', ['id' => $this->otherOrganizationPatient->id, 'deleted_at' => null]);
    }

    public function test_own_organization_patient_can_still_be_updated(): void
    {
        $patient = Patient::factory()->create(['organization_id' => $this->ownOrganization->id]);

        $response = $this->put('/admin/patients/'.$patient->public_id, [
            'organization_id' => $this->ownOrganization->id,
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'status' => 'active',
        ]);

        $response->assertRedirect('/admin/patients');
        $this->assertDatabaseHas('patients', ['id' => $patient->id, 'first_name' => 'Updated']);
    }

    public function test_store_requires_the_create_permission(): void
    {
        $this->user->revokePermissionTo('patients.create');

        $response = $this->post('/admin/patients', [
            'organization_id' => $this->ownOrganization->id,
            'first_name' => 'New',
            'last_name' => 'Patient',
            'status' => 'active',
            'emergency_contact' => ['name' => 'EC', 'phone' => '000'],
        ]);

        $response->assertForbidden();
    }

    public function test_cannot_merge_a_patient_belonging_to_another_organization(): void
    {
        $ownPatient = Patient::factory()->create(['organization_id' => $this->ownOrganization->id]);

        $response = $this->post('/admin/patients/merge', [
            'master_patient_id' => $ownPatient->id,
            'duplicate_patient_id' => $this->otherOrganizationPatient->id,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('patients', ['id' => $this->otherOrganizationPatient->id, 'status' => 'active']);
    }

    public function test_search_only_returns_patients_from_the_active_tenant_organization(): void
    {
        $ownPatient = Patient::factory()->create(['organization_id' => $this->ownOrganization->id, 'first_name' => 'Findme']);

        $response = $this->get('/admin/patients/search?q=Findme');

        $response->assertOk();
        $response->assertViewHas('patients', function ($patients) use ($ownPatient) {
            return $patients->contains('id', $ownPatient->id)
                && ! $patients->contains('id', $this->otherOrganizationPatient->id);
        });
    }

    public function test_cannot_download_another_organizations_patient_document(): void
    {
        $document = PatientDocument::factory()->create([
            'organization_id' => $this->otherOrganization->id,
            'patient_id' => $this->otherOrganizationPatient->id,
        ]);

        $response = $this->get("/admin/patients/{$this->otherOrganizationPatient->public_id}/documents/{$document->public_id}/download");

        $response->assertForbidden();
    }
}
