<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PatientControllerTest extends TestCase
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

        Permission::create(['name' => 'manage companies', 'guard_name' => 'web']);
        Permission::create(['name' => 'patients.create', 'guard_name' => 'web']);
        Permission::create(['name' => 'patients.update', 'guard_name' => 'web']);
        Permission::create(['name' => 'patients.delete', 'guard_name' => 'web']);
        $this->user->givePermissionTo('manage companies');
        $this->user->givePermissionTo('patients.create');
        $this->user->givePermissionTo('patients.update');
        $this->user->givePermissionTo('patients.delete');

        $this->actingAs($this->user);
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
            'company_id' => $this->company->id,
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
            'company_id' => $this->company->id,
        ]);
    }

    public function test_user_can_view_patient(): void
    {
        $patient = Patient::factory()->create(['company_id' => $this->company->id]);

        $response = $this->get('/admin/patients/'.$patient->id);

        $response->assertStatus(200);
        $response->assertViewHas('patient');
    }

    public function test_user_can_update_patient(): void
    {
        $patient = Patient::factory()->create(['company_id' => $this->company->id]);

        $response = $this->put('/admin/patients/'.$patient->id, [
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
        $patient = Patient::factory()->create(['company_id' => $this->company->id]);

        $response = $this->delete('/admin/patients/'.$patient->id);

        $response->assertRedirect('/admin/patients');
        $this->assertSoftDeleted('patients', [
            'id' => $patient->id,
        ]);
    }
}
