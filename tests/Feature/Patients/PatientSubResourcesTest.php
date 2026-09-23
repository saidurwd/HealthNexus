<?php

namespace Tests\Feature\Patients;

use App\Models\Company;
use App\Models\Patient;
use App\Models\User;
use App\Services\TenantContextResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PatientSubResourcesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $company;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company->id, ['access_level' => 'admin']);

        foreach (['patients.view', 'patients.update', 'patients.consents.view', 'patients.consents.manage'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $this->user->givePermissionTo($permission);
        }

        $this->actingAs($this->user);
        session()->put('tenant_company_id', $this->company->id);
        app(TenantContextResolver::class)->setCompanyId($this->company->id);

        $this->patient = Patient::factory()->create(['company_id' => $this->company->id]);
    }

    public function test_can_add_an_address(): void
    {
        $response = $this->post("/admin/patients/{$this->patient->id}/addresses", [
            'address_type' => 'present',
            'line1' => '123 Main St',
            'city' => 'Dhaka',
            'is_primary' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('patient_addresses', [
            'patient_id' => $this->patient->id, 'address_type' => 'present', 'city' => 'Dhaka', 'is_primary' => true,
        ]);
    }

    public function test_a_second_primary_address_of_the_same_type_demotes_the_first(): void
    {
        $this->post("/admin/patients/{$this->patient->id}/addresses", ['address_type' => 'present', 'is_primary' => true]);
        $this->post("/admin/patients/{$this->patient->id}/addresses", ['address_type' => 'present', 'is_primary' => true]);

        $this->assertSame(1, $this->patient->addresses()->where('address_type', 'present')->where('is_primary', true)->count());
        $this->assertSame(2, $this->patient->addresses()->where('address_type', 'present')->count());
    }

    public function test_can_add_a_guardian(): void
    {
        $response = $this->post("/admin/patients/{$this->patient->id}/guardians", [
            'name' => 'John Guardian',
            'relationship' => 'Father',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('patient_guardians', ['patient_id' => $this->patient->id, 'name' => 'John Guardian']);
    }

    public function test_can_set_preferences(): void
    {
        $response = $this->put("/admin/patients/{$this->patient->id}/preferences", [
            'preferred_language' => 'bn',
            'preferred_contact_method' => 'sms',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('patient_preferences', [
            'patient_id' => $this->patient->id, 'preferred_language' => 'bn', 'preferred_contact_method' => 'sms',
        ]);
    }

    public function test_can_grant_and_withdraw_consent(): void
    {
        $this->post("/admin/patients/{$this->patient->id}/consents", ['consent_type' => 'treatment']);

        $this->assertDatabaseHas('patient_consents', [
            'patient_id' => $this->patient->id, 'consent_type' => 'treatment', 'status' => 'active', 'version' => 1,
        ]);

        $consent = $this->patient->consents()->first();

        $this->put("/admin/patients/{$this->patient->id}/consents/{$consent->id}/withdraw");

        $this->assertDatabaseHas('patient_consents', ['id' => $consent->id, 'status' => 'withdrawn']);
    }

    public function test_granting_a_new_consent_of_the_same_type_versions_instead_of_overwriting(): void
    {
        $this->post("/admin/patients/{$this->patient->id}/consents", ['consent_type' => 'treatment']);
        $this->post("/admin/patients/{$this->patient->id}/consents", ['consent_type' => 'treatment']);

        $this->assertSame(2, $this->patient->consents()->where('consent_type', 'treatment')->count());
        $this->assertSame(1, $this->patient->consents()->where('status', 'active')->count());
        $this->assertDatabaseHas('patient_consents', ['patient_id' => $this->patient->id, 'consent_type' => 'treatment', 'version' => 2, 'status' => 'active']);
    }
}
