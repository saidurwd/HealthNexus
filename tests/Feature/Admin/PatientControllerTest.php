<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\PatientAllergy;
use App\Models\PatientDocument;
use App\Models\PatientHistory;
use App\Models\User;
use App\Services\TenantContextResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PatientControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->branch = Branch::factory()->create(['organization_id' => $this->organization->id]);

        $this->user->organizations()->attach($this->organization->id, ['access_level' => 'admin']);
        $this->user->branches()->attach($this->branch->id, ['access_level' => 'manager', 'organization_id' => $this->organization->id]);

        Permission::create(['name' => 'manage organizations', 'guard_name' => 'web']);
        Permission::create(['name' => 'patients.view', 'guard_name' => 'web']);
        Permission::create(['name' => 'patients.create', 'guard_name' => 'web']);
        Permission::create(['name' => 'patients.update', 'guard_name' => 'web']);
        Permission::create(['name' => 'patients.delete', 'guard_name' => 'web']);
        Permission::create(['name' => 'patients.merge', 'guard_name' => 'web']);
        Permission::create(['name' => 'patients.documents.view', 'guard_name' => 'web']);
        Permission::create(['name' => 'patients.documents.manage', 'guard_name' => 'web']);
        $this->user->givePermissionTo('manage organizations');
        $this->user->givePermissionTo('patients.view');
        $this->user->givePermissionTo('patients.create');
        $this->user->givePermissionTo('patients.update');
        $this->user->givePermissionTo('patients.delete');
        $this->user->givePermissionTo('patients.merge');
        $this->user->givePermissionTo('patients.documents.view');
        $this->user->givePermissionTo('patients.documents.manage');

        $this->actingAs($this->user);

        session()->put('tenant_organization_id', $this->organization->id);
        app(TenantContextResolver::class)->setOrganizationId($this->organization->id);
    }

    public function test_user_can_view_patients_index(): void
    {
        $response = $this->get('/admin/patients');

        $response->assertStatus(200);
        $response->assertViewHas('patients');
    }

    public function test_user_can_create_patient(): void
    {
        $patientData = [
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'first_name' => 'John',
            'middle_name' => 'Doe',
            'last_name' => 'Smith',
            'date_of_birth' => '1990-01-01',
            'sex' => 'M',
            'blood_group' => 'A+',
            'phone' => '1234567890',
            'email' => 'john@example.com',
            'address' => '123 Main St',
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'country' => 'Bangladesh',
            'postal_code' => '1000',
            'status' => 'active',
        ];

        $response = $this->post('/admin/patients', $patientData);

        $response->assertRedirect('/admin/patients');
        $this->assertDatabaseHas('patients', [
            'first_name' => 'John',
            'last_name' => 'Smith',
            'organization_id' => $this->organization->id,
        ]);
    }

    public function test_user_can_view_patient(): void
    {
        $patient = Patient::factory()->create(['organization_id' => $this->organization->id]);

        $response = $this->get('/admin/patients/'.$patient->public_id);

        $response->assertStatus(200);
        $response->assertViewHas('patient');
    }

    public function test_user_can_update_patient(): void
    {
        $patient = Patient::factory()->create(['organization_id' => $this->organization->id]);

        $response = $this->put('/admin/patients/'.$patient->public_id, [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'status' => 'active',
        ]);

        $response->assertRedirect('/admin/patients');
        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'first_name' => 'Jane',
        ]);
    }

    public function test_user_can_delete_patient(): void
    {
        $patient = Patient::factory()->create(['organization_id' => $this->organization->id]);

        $response = $this->delete('/admin/patients/'.$patient->public_id);

        $response->assertRedirect('/admin/patients');
        $this->assertSoftDeleted('patients', [
            'id' => $patient->id,
        ]);
    }

    public function test_user_can_search_patients(): void
    {
        Patient::factory()->create([
            'organization_id' => $this->organization->id,
            'first_name' => 'Alice',
            'last_name' => 'Anderson',
            'national_identifier' => 'NID-001',
        ]);
        Patient::factory()->create([
            'organization_id' => $this->organization->id,
            'first_name' => 'Bob',
            'last_name' => 'Brown',
        ]);

        $response = $this->get('/admin/patients/search?q=Alice');

        $response->assertStatus(200);
        $response->assertViewHas('patients');
    }

    public function test_user_can_detect_duplicates(): void
    {
        Patient::factory()->create([
            'organization_id' => $this->organization->id,
            'first_name' => 'John',
            'last_name' => 'Smith',
            'national_identifier' => 'NID-123',
        ]);

        $response = $this->postJson('/admin/patients/detect-duplicates', [
            'first_name' => 'John',
            'last_name' => 'Smith',
            'national_identifier' => 'NID-123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['duplicates']);
        $response->assertJsonCount(1, 'duplicates');
    }

    public function test_user_can_merge_patients(): void
    {
        $master = Patient::factory()->create([
            'organization_id' => $this->organization->id,
            'first_name' => 'Master',
            'last_name' => 'Patient',
        ]);
        $duplicate = Patient::factory()->create([
            'organization_id' => $this->organization->id,
            'first_name' => 'Duplicate',
            'last_name' => 'Patient',
        ]);

        PatientAllergy::factory()->create(['organization_id' => $this->organization->id, 'patient_id' => $duplicate->id]);

        $response = $this->post('/admin/patients/merge', [
            'master_patient_id' => $master->id,
            'duplicate_patient_id' => $duplicate->id,
        ]);

        $response->assertRedirect('/admin/patients/'.$master->public_id);
        $this->assertSoftDeleted('patients', ['id' => $duplicate->id]);
        $this->assertDatabaseHas('patient_allergies', ['patient_id' => $master->id]);
    }

    public function test_user_can_view_patient_timeline(): void
    {
        $patient = Patient::factory()->create(['organization_id' => $this->organization->id]);
        PatientAllergy::factory()->create([
            'organization_id' => $this->organization->id,
            'patient_id' => $patient->id,
        ]);

        $response = $this->get('/admin/patients/'.$patient->public_id.'/timeline');

        $response->assertStatus(200);
        $response->assertViewHas('timeline');
    }

    public function test_user_can_view_patient_documents(): void
    {
        $patient = Patient::factory()->create(['organization_id' => $this->organization->id]);

        PatientDocument::factory()->create([
            'organization_id' => $this->organization->id,
            'patient_id' => $patient->id,
        ]);

        $response = $this->get('/admin/patients/'.$patient->public_id.'/documents');

        $response->assertStatus(200);
        $response->assertViewHas('documents');
    }

    public function test_user_can_add_allergy(): void
    {
        $patient = Patient::factory()->create(['organization_id' => $this->organization->id]);

        $response = $this->post('/admin/patients/'.$patient->public_id.'/allergies', [
            'substance' => 'Penicillin',
            'severity' => 'severe',
            'reaction' => 'rash',
            'notes' => 'Severe rash on exposure',
            'is_active' => true,
        ]);

        $response->assertRedirect('/admin/patients/'.$patient->public_id.'/allergies');
        $this->assertDatabaseHas('patient_allergies', [
            'patient_id' => $patient->id,
            'substance' => 'Penicillin',
        ]);
    }

    public function test_user_can_add_medical_history(): void
    {
        $patient = Patient::factory()->create(['organization_id' => $this->organization->id]);

        $response = $this->post('/admin/patients/'.$patient->public_id.'/history', [
            'condition' => 'Hypertension',
            'description' => 'High blood pressure diagnosed in 2020',
            'diagnosed_at' => '2020-01-01',
            'is_active' => true,
        ]);

        $response->assertRedirect('/admin/patients/'.$patient->public_id.'/history');
        $this->assertDatabaseHas('patient_histories', [
            'patient_id' => $patient->id,
            'condition' => 'Hypertension',
        ]);
    }

    public function test_user_can_delete_allergy(): void
    {
        $patient = Patient::factory()->create(['organization_id' => $this->organization->id]);
        $allergy = PatientAllergy::factory()->create([
            'organization_id' => $this->organization->id,
            'patient_id' => $patient->id,
        ]);

        $response = $this->delete('/admin/patients/'.$patient->public_id.'/allergies/'.$allergy->id);

        $response->assertRedirect('/admin/patients/'.$patient->public_id.'/allergies');
        $this->assertSoftDeleted('patient_allergies', ['id' => $allergy->id]);
    }

    public function test_user_can_delete_history(): void
    {
        $patient = Patient::factory()->create(['organization_id' => $this->organization->id]);
        $history = PatientHistory::factory()->create([
            'organization_id' => $this->organization->id,
            'patient_id' => $patient->id,
        ]);

        $response = $this->delete('/admin/patients/'.$patient->public_id.'/history/'.$history->id);

        $response->assertRedirect('/admin/patients/'.$patient->public_id.'/history');
        $this->assertSoftDeleted('patient_histories', ['id' => $history->id]);
    }
}
