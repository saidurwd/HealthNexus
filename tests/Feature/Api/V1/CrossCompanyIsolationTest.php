<?php

namespace Tests\Feature\Api\V1;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrossCompanyIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $companyA;

    private Company $companyB;

    private Branch $branchA;

    private Branch $branchB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->companyA = Company::factory()->create(['name' => 'Company A', 'code' => 'COMP-A']);
        $this->companyB = Company::factory()->create(['name' => 'Company B', 'code' => 'COMP-B']);

        $this->branchA = Branch::factory()->create(['company_id' => $this->companyA->id, 'name' => 'Branch A']);
        $this->branchB = Branch::factory()->create(['company_id' => $this->companyB->id, 'name' => 'Branch B']);

        $this->user->companies()->attach($this->companyA->id, ['access_level' => 'admin']);
        $this->user->companies()->attach($this->companyB->id, ['access_level' => 'admin']);

        $this->user->branches()->attach($this->branchA->id, ['access_level' => 'manager', 'company_id' => $this->companyA->id]);
        $this->user->branches()->attach($this->branchB->id, ['access_level' => 'manager', 'company_id' => $this->companyB->id]);

        $token = $this->user->createToken('test-token')->plainTextToken;
        $this->withHeader('Authorization', 'Bearer '.$token);
    }

    public function test_user_cannot_access_company_they_are_not_assigned_to(): void
    {
        $otherCompany = Company::factory()->create(['name' => 'Other Company', 'code' => 'OTHER']);

        $response = $this->getJson("/api/v1/companies/{$otherCompany->id}");

        $response->assertStatus(403);
    }

    public function test_user_cannot_access_branch_from_company_they_are_not_assigned_to(): void
    {
        $otherCompany = Company::factory()->create();
        $otherBranch = Branch::factory()->create(['company_id' => $otherCompany->id]);

        $response = $this->getJson("/api/v1/companies/{$otherCompany->id}/branches/{$otherBranch->id}");

        $response->assertStatus(403);
    }

    public function test_user_cannot_see_other_companies_users(): void
    {
        $otherUser = User::factory()->create();
        $otherUser->companies()->attach($this->companyB->id, ['access_level' => 'staff']);

        $response = $this->withHeader('X-Company-Id', $this->companyA->id)->getJson("/api/v1/companies/{$this->companyA->id}/users");

        $response->assertStatus(200);
        $response->assertJsonMissing(['email' => $otherUser->email]);
    }

    public function test_user_can_only_see_users_in_their_companies(): void
    {
        $userInA = User::factory()->create();
        $userInA->companies()->attach($this->companyA->id, ['access_level' => 'staff']);

        $userInB = User::factory()->create();
        $userInB->companies()->attach($this->companyB->id, ['access_level' => 'staff']);

        $response = $this->withHeader('X-Company-Id', $this->companyA->id)->getJson("/api/v1/companies/{$this->companyA->id}/users");

        $response->assertStatus(200);
        $response->assertJsonFragment(['email' => $userInA->email]);
        $response->assertJsonMissing(['email' => $userInB->email]);
    }

    public function test_user_cannot_update_company_they_dont_own(): void
    {
        $otherCompany = Company::factory()->create();
        $this->user->companies()->attach($otherCompany->id, ['access_level' => 'staff']);

        $response = $this->putJson("/api/v1/companies/{$otherCompany->id}", [
            'name' => 'Hacked Name',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_cannot_delete_company_they_dont_own(): void
    {
        $otherCompany = Company::factory()->create();
        $this->user->companies()->attach($otherCompany->id, ['access_level' => 'staff']);

        $response = $this->deleteJson("/api/v1/companies/{$otherCompany->id}");

        $response->assertStatus(403);
    }
}
