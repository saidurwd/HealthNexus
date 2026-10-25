<?php

namespace Tests\Feature\Api\V1;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrossOrganizationIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organizationA;

    private Organization $organizationB;

    private Branch $branchA;

    private Branch $branchB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->organizationA = Organization::factory()->create(['name' => 'Organization A', 'code' => 'COMP-A']);
        $this->organizationB = Organization::factory()->create(['name' => 'Organization B', 'code' => 'COMP-B']);

        $this->branchA = Branch::factory()->create(['organization_id' => $this->organizationA->id, 'name' => 'Branch A']);
        $this->branchB = Branch::factory()->create(['organization_id' => $this->organizationB->id, 'name' => 'Branch B']);

        $this->user->organizations()->attach($this->organizationA->id, ['access_level' => 'admin']);
        $this->user->organizations()->attach($this->organizationB->id, ['access_level' => 'admin']);

        $this->user->branches()->attach($this->branchA->id, ['access_level' => 'manager', 'organization_id' => $this->organizationA->id]);
        $this->user->branches()->attach($this->branchB->id, ['access_level' => 'manager', 'organization_id' => $this->organizationB->id]);

        $token = $this->user->createToken('test-token')->plainTextToken;
        $this->withHeader('Authorization', 'Bearer '.$token);
    }

    public function test_user_cannot_access_organization_they_are_not_assigned_to(): void
    {
        $otherOrganization = Organization::factory()->create(['name' => 'Other Organization', 'code' => 'OTHER']);

        $response = $this->getJson("/api/v1/organizations/{$otherOrganization->id}");

        $response->assertStatus(403);
    }

    public function test_user_cannot_access_branch_from_organization_they_are_not_assigned_to(): void
    {
        $otherOrganization = Organization::factory()->create();
        $otherBranch = Branch::factory()->create(['organization_id' => $otherOrganization->id]);

        $response = $this->getJson("/api/v1/organizations/{$otherOrganization->id}/branches/{$otherBranch->id}");

        $response->assertStatus(403);
    }

    public function test_user_cannot_see_other_organizations_users(): void
    {
        $otherUser = User::factory()->create();
        $otherUser->organizations()->attach($this->organizationB->id, ['access_level' => 'staff']);

        $response = $this->withHeader('X-Organization-Id', $this->organizationA->id)->getJson("/api/v1/organizations/{$this->organizationA->id}/users");

        $response->assertStatus(200);
        $response->assertJsonMissing(['email' => $otherUser->email]);
    }

    public function test_user_can_only_see_users_in_their_organizations(): void
    {
        $userInA = User::factory()->create();
        $userInA->organizations()->attach($this->organizationA->id, ['access_level' => 'staff']);

        $userInB = User::factory()->create();
        $userInB->organizations()->attach($this->organizationB->id, ['access_level' => 'staff']);

        $response = $this->withHeader('X-Organization-Id', $this->organizationA->id)->getJson("/api/v1/organizations/{$this->organizationA->id}/users");

        $response->assertStatus(200);
        $response->assertJsonFragment(['email' => $userInA->email]);
        $response->assertJsonMissing(['email' => $userInB->email]);
    }

    public function test_user_cannot_update_organization_they_dont_own(): void
    {
        $otherOrganization = Organization::factory()->create();
        $this->user->organizations()->attach($otherOrganization->id, ['access_level' => 'staff']);

        $response = $this->putJson("/api/v1/organizations/{$otherOrganization->id}", [
            'name' => 'Hacked Name',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_cannot_delete_organization_they_dont_own(): void
    {
        $otherOrganization = Organization::factory()->create();
        $this->user->organizations()->attach($otherOrganization->id, ['access_level' => 'staff']);

        $response = $this->deleteJson("/api/v1/organizations/{$otherOrganization->id}");

        $response->assertStatus(403);
    }
}
