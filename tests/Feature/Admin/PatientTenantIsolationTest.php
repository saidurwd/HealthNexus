<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Patient;
use App\Models\User;
use App\Services\TenantContextResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * PatientPolicy::update()/delete() previously checked only the Spatie permission string, with no
 * check that the target patient belongs to a company the user is actually assigned to — a user
 * with patients.update could update another company's patient if they could reach the route.
 * These tests guard against that regression.
 */
class PatientTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $ownCompany;

    private Company $otherCompany;

    private Patient $otherCompanyPatient;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->ownCompany = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $this->ownCompany->id]);
        $this->otherCompany = Company::factory()->create();

        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->ownCompany->id, ['access_level' => 'admin']);
        $this->user->branches()->attach($branch->id, ['access_level' => 'manager', 'company_id' => $this->ownCompany->id]);

        foreach (['manage companies', 'patients.view', 'patients.create', 'patients.update', 'patients.delete', 'patients.merge', 'patients.documents.view', 'patients.documents.manage'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $this->user->givePermissionTo($permission);
        }

        $this->actingAs($this->user);
        session()->put('tenant_company_id', $this->ownCompany->id);
        app(TenantContextResolver::class)->setCompanyId($this->ownCompany->id);

        $this->otherCompanyPatient = Patient::factory()->create(['company_id' => $this->otherCompany->id]);
    }

    public function test_cannot_update_another_companys_patient_despite_having_permission(): void
    {
        $response = $this->put('/admin/patients/'.$this->otherCompanyPatient->id, [
            'company_id' => $this->otherCompany->id,
            'first_name' => 'Hacked',
            'last_name' => 'Name',
            'status' => 'active',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('patients', ['id' => $this->otherCompanyPatient->id, 'first_name' => 'Hacked']);
    }

    public function test_cannot_delete_another_companys_patient_despite_having_permission(): void
    {
        $response = $this->delete('/admin/patients/'.$this->otherCompanyPatient->id);

        $response->assertForbidden();
        $this->assertDatabaseHas('patients', ['id' => $this->otherCompanyPatient->id, 'deleted_at' => null]);
    }

    public function test_own_company_patient_can_still_be_updated(): void
    {
        $patient = Patient::factory()->create(['company_id' => $this->ownCompany->id]);

        $response = $this->put('/admin/patients/'.$patient->id, [
            'company_id' => $this->ownCompany->id,
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
            'company_id' => $this->ownCompany->id,
            'first_name' => 'New',
            'last_name' => 'Patient',
            'status' => 'active',
            'emergency_contact' => ['name' => 'EC', 'phone' => '000'],
        ]);

        $response->assertForbidden();
    }

    public function test_cannot_merge_a_patient_belonging_to_another_company(): void
    {
        $ownPatient = Patient::factory()->create(['company_id' => $this->ownCompany->id]);

        $response = $this->post('/admin/patients/merge', [
            'master_patient_id' => $ownPatient->id,
            'duplicate_patient_id' => $this->otherCompanyPatient->id,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('patients', ['id' => $this->otherCompanyPatient->id, 'status' => 'active']);
    }

    public function test_search_only_returns_patients_from_the_active_tenant_company(): void
    {
        $ownPatient = Patient::factory()->create(['company_id' => $this->ownCompany->id, 'first_name' => 'Findme']);

        $response = $this->get('/admin/patients/search?q=Findme');

        $response->assertOk();
        $response->assertViewHas('patients', function ($patients) use ($ownPatient) {
            return $patients->contains('id', $ownPatient->id)
                && ! $patients->contains('id', $this->otherCompanyPatient->id);
        });
    }

    public function test_cannot_download_another_companys_patient_document(): void
    {
        $document = \App\Models\PatientDocument::factory()->create([
            'company_id' => $this->otherCompany->id,
            'patient_id' => $this->otherCompanyPatient->id,
        ]);

        $response = $this->get("/admin/patients/{$this->otherCompanyPatient->id}/documents/{$document->id}/download");

        $response->assertForbidden();
    }
}
