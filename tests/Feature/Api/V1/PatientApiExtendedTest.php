<?php

namespace Tests\Feature\Api\V1;

use App\Models\Company;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PatientApiExtendedTest extends TestCase
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

        foreach (['patients.create', 'patients.update', 'patients.merge'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $this->user->givePermissionTo($permission);
        }

        $token = $this->user->createToken('test-token')->plainTextToken;
        $this->withHeader('Authorization', 'Bearer '.$token);
        $this->withHeader('X-Company-Id', (string) $this->company->id);
    }

    public function test_search_returns_matching_patients(): void
    {
        Patient::factory()->create(['company_id' => $this->company->id, 'first_name' => 'Findable']);
        Patient::factory()->create(['company_id' => $this->company->id, 'first_name' => 'Other']);

        $response = $this->get('/api/v1/patients/search?q=Findable');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_can_add_and_list_identifiers(): void
    {
        $patient = Patient::factory()->create(['company_id' => $this->company->id]);

        $this->post("/api/v1/patients/{$patient->id}/identifiers", [
            'identifier_type' => 'passport', 'identifier_value' => 'X123',
        ])->assertStatus(201);

        $response = $this->get("/api/v1/patients/{$patient->id}/identifiers");

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_can_add_and_list_contacts(): void
    {
        $patient = Patient::factory()->create(['company_id' => $this->company->id]);

        $this->post("/api/v1/patients/{$patient->id}/contacts", ['name' => 'Emergency Contact', 'phone' => '01700000000'])
            ->assertStatus(201);

        $this->get("/api/v1/patients/{$patient->id}/contacts")->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_can_add_and_list_addresses(): void
    {
        $patient = Patient::factory()->create(['company_id' => $this->company->id]);

        $this->post("/api/v1/patients/{$patient->id}/addresses", ['address_type' => 'present', 'city' => 'Dhaka'])
            ->assertStatus(201);

        $this->get("/api/v1/patients/{$patient->id}/addresses")->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_timeline_returns_events(): void
    {
        $patient = Patient::factory()->create(['company_id' => $this->company->id]);

        $response = $this->get("/api/v1/patients/{$patient->id}/timeline");

        $response->assertOk();
    }

    public function test_duplicate_check_finds_matching_patients(): void
    {
        Patient::factory()->create(['company_id' => $this->company->id, 'first_name' => 'Jane', 'last_name' => 'Doe']);

        $response = $this->post('/api/v1/patients/duplicate-check', ['first_name' => 'Jane', 'last_name' => 'Doe']);

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_can_merge_two_patients(): void
    {
        $master = Patient::factory()->create(['company_id' => $this->company->id]);
        $duplicate = Patient::factory()->create(['company_id' => $this->company->id]);

        $response = $this->post('/api/v1/patients/merge', [
            'master_patient_id' => $master->id,
            'duplicate_patient_id' => $duplicate->id,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('patients', ['id' => $duplicate->id, 'status' => 'merged', 'merged_into_patient_id' => $master->id]);
    }
}
