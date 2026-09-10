<?php

namespace Tests\Feature\Api\V1;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrossBranchIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $company;

    private Branch $branchA;

    private Branch $branchB;

    private Department $deptA;

    private Department $deptB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->company = Company::factory()->create(['name' => 'Test Company', 'code' => 'TEST']);

        $this->branchA = Branch::factory()->create(['company_id' => $this->company->id, 'name' => 'Branch A']);
        $this->branchB = Branch::factory()->create(['company_id' => $this->company->id, 'name' => 'Branch B']);

        $this->deptA = Department::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'name' => 'Department A',
        ]);

        $this->deptB = Department::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branchB->id,
            'name' => 'Department B',
        ]);

        $this->user->companies()->attach($this->company->id, ['access_level' => 'admin']);
        $this->user->branches()->attach($this->branchA->id, ['access_level' => 'manager', 'company_id' => $this->company->id]);
        $this->user->branches()->attach($this->branchB->id, ['access_level' => 'manager', 'company_id' => $this->company->id]);

        $token = $this->user->createToken('test-token')->plainTextToken;
        $this->withHeader('Authorization', 'Bearer '.$token);
    }

    public function test_user_cannot_access_branch_they_are_not_assigned_to(): void
    {
        $otherBranch = Branch::factory()->create(['company_id' => $this->company->id, 'name' => 'Other Branch']);
        $this->user->branches()->detach($otherBranch->id);

        $response = $this->getJson("/api/v1/companies/{$this->company->id}/branches/{$otherBranch->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_access_their_assigned_branches(): void
    {
        $responseA = $this->withHeader('X-Company-Id', $this->company->id)->getJson("/api/v1/companies/{$this->company->id}/branches/{$this->branchA->id}");
        $responseB = $this->withHeader('X-Company-Id', $this->company->id)->getJson("/api/v1/companies/{$this->company->id}/branches/{$this->branchB->id}");

        $responseA->assertStatus(200);
        $responseB->assertStatus(200);
    }

    public function test_user_cannot_see_departments_from_other_branches(): void
    {
        $otherDept = Department::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branchB->id,
            'name' => 'Other Dept',
        ]);

        $response = $this->withHeader('X-Company-Id', $this->company->id)->getJson("/api/v1/companies/{$this->company->id}/branches/{$this->branchA->id}/departments");

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => $this->deptA->name]);
        $response->assertJsonMissing(['name' => $otherDept->name]);
    }

    public function test_user_can_see_departments_in_their_branch(): void
    {
        $response = $this->withHeader('X-Company-Id', $this->company->id)->getJson("/api/v1/companies/{$this->company->id}/branches/{$this->branchA->id}/departments");

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => $this->deptA->name]);
    }

    public function test_user_cannot_create_department_in_branch_they_are_not_assigned_to(): void
    {
        $otherBranch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->user->branches()->detach($otherBranch->id);

        $response = $this->withHeader('X-Company-Id', $this->company->id)->postJson("/api/v1/companies/{$this->company->id}/branches/{$otherBranch->id}/departments", [
            'name' => 'New Department',
            'code' => 'NEW-DEPT',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_cannot_update_branch_they_are_not_assigned_to(): void
    {
        $otherBranch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->user->branches()->detach($otherBranch->id);

        $response = $this->withHeader('X-Company-Id', $this->company->id)->putJson("/api/v1/companies/{$this->company->id}/branches/{$otherBranch->id}", [
            'name' => 'Hacked Branch',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_cannot_delete_branch_they_are_not_assigned_to(): void
    {
        $otherBranch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->user->branches()->detach($otherBranch->id);

        $response = $this->withHeader('X-Company-Id', $this->company->id)->deleteJson("/api/v1/companies/{$this->company->id}/branches/{$otherBranch->id}");

        $response->assertStatus(403);
    }
}
