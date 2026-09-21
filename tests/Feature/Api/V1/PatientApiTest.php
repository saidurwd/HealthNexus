<?php

namespace Tests\Feature\Api\V1;

use App\Models\Company;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PatientApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::factory()->create();

        $this->user->companies()->attach($this->company->id, ['access_level' => 'admin']);

        Permission::create(['name' => 'patients.create', 'guard_name' => 'web']);
        Permission::create(['name' => 'patients.update', 'guard_name' => 'web']);
        Permission::create(['name' => 'patients.delete', 'guard_name' => 'web']);
        $this->user->givePermissionTo('patients.create');
        $this->user->givePermissionTo('patients.update');
        $this->user->givePermissionTo('patients.delete');

        $token = $this->user->createToken('test-token')->plainTextToken;
        $this->withHeader('Authorization', 'Bearer '.$token);
        $this->withHeader('X-Company-Id', (string) $this->company->id);
    }

    public function test_user_can_list_patients(): void
    {
        Patient::factory()->count(3)->create(['company_id' => $this->company->id]);

        $response = $this->get('/api/v1/patients');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_create_patient(): void
    {
        $response = $this->post('/api/v1/patients', [
            'company_id' => $this->company->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'sex' => 'M',
            'status' => 'active',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.first_name', 'John')
            ->assertJsonPath('data.last_name', 'Doe');
    }

    public function test_user_can_view_patient(): void
    {
        $patient = Patient::factory()->create(['company_id' => $this->company->id]);

        $response = $this->get('/api/v1/patients/'.$patient->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $patient->id);
    }

    public function test_user_can_update_patient(): void
    {
        $patient = Patient::factory()->create(['company_id' => $this->company->id]);

        $response = $this->put('/api/v1/patients/'.$patient->id, [
            'first_name' => 'Jane',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.first_name', 'Jane');
    }

    public function test_user_can_delete_patient(): void
    {
        $patient = Patient::factory()->create(['company_id' => $this->company->id]);

        $response = $this->delete('/api/v1/patients/'.$patient->id);

        $response->assertStatus(200);
        $this->assertSoftDeleted('patients', [
            'id' => $patient->id,
        ]);
    }
}
