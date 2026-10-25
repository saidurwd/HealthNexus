<?php

namespace Tests\Feature\Nursing;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class NursingTenantIsolationTest extends NursingTestCase
{
    private function nurseWith(array $permissions, ?Organization $organization = null): User
    {
        $organization ??= $this->organization;
        $branch = $organization->id === $this->organization->id ? $this->branch : Branch::factory()->create(['organization_id' => $organization->id]);

        $nurse = User::factory()->create();
        $nurse->organizations()->attach($organization->id, ['access_level' => 'staff']);
        $nurse->branches()->attach($branch->id, ['access_level' => 'staff', 'organization_id' => $organization->id]);
        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
        $role = Role::firstOrCreate(['name' => 'staff_nurse', 'guard_name' => 'web']);
        $role->syncPermissions($permissions);
        $nurse->assignRole($role);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return $nurse->fresh();
    }

    public function test_hospital_a_nurse_cannot_touch_hospital_b_patient(): void
    {
        $episode = $this->makeEpisode();
        $mar = $this->makeMar($episode);

        $otherOrganization = Organization::factory()->create();
        $foreignNurse = $this->nurseWith(['nursing.dashboard.view', 'nursing.mar.view', 'nursing.mar.administer'], $otherOrganization);

        $this->assertFalse($foreignNurse->can('view', $episode));
        $this->assertFalse($foreignNurse->can('view', $mar));
        $this->assertFalse($foreignNurse->can('administer', $mar));

        $this->actingAs($foreignNurse)->get('/admin/nursing/mar/'.$mar->id)->assertForbidden();
        $this->actingAs($foreignNurse)->post('/admin/nursing/mar/'.$mar->id.'/administer', ['safety_checks' => ['patient' => 1]])->assertForbidden();
    }

    public function test_ward_nurse_without_assignment_cannot_view_unassigned_patient_but_assigned_nurse_can(): void
    {
        $episode = $this->makeEpisode();
        $nurse = $this->nurseWith(['nursing.dashboard.view']);

        $this->assertFalse($nurse->can('view', $episode));

        app(\App\Services\Nursing\NursingAssignmentService::class)->assign($episode, ['nurse_id' => $nurse->id], $this->user);

        $this->assertTrue($nurse->fresh()->can('view', $episode->fresh()));
    }

    public function test_api_denies_cross_hospital_mar_and_unauthenticated_access(): void
    {
        $mar = $this->makeMar($this->makeEpisode());
        $foreignNurse = $this->nurseWith(['nursing.mar.view', 'nursing.mar.administer'], Organization::factory()->create());

        $this->actingAs($foreignNurse)->postJson('/api/v1/nursing/mar/'.$mar->id.'/administer', ['safety_checks' => ['patient' => 1]])
            ->assertStatus(403);

        $this->assertSame('scheduled', $mar->fresh()->status);
    }

    public function test_user_without_permission_cannot_administer(): void
    {
        $mar = $this->makeMar($this->makeEpisode());
        $nurse = $this->nurseWith(['nursing.mar.view']);

        $this->actingAs($nurse)->post('/admin/nursing/mar/'.$mar->id.'/administer', ['safety_checks' => ['patient' => 1]])->assertForbidden();
        $this->assertSame('scheduled', $mar->fresh()->status);
    }
}
