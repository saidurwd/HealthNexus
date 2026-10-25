<?php

namespace Tests\Feature\Icu;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class IcuTenantIsolationTest extends IcuTestCase
{
    private function userWith(array $permissions, ?Organization $organization = null): User
    {
        $organization ??= $this->organization;
        $branch = $organization->id === $this->organization->id ? $this->branch : Branch::factory()->create(['organization_id' => $organization->id]);

        $user = User::factory()->create();
        $user->organizations()->attach($organization->id, ['access_level' => 'staff']);
        $user->branches()->attach($branch->id, ['access_level' => 'staff', 'organization_id' => $organization->id]);

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $role = Role::firstOrCreate(['name' => 'icu_test_role', 'guard_name' => 'web']);
        $role->syncPermissions($permissions);
        $user->assignRole($role);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return $user->fresh();
    }

    public function test_hospital_a_user_cannot_view_hospital_b_icu_episode(): void
    {
        $episode = $this->makeEpisode();
        $otherOrganization = Organization::factory()->create();
        $foreignUser = $this->userWith(['icu.episode.view'], $otherOrganization);

        $this->assertFalse($foreignUser->can('view', $episode));

        $this->actingAs($foreignUser)->get('/admin/icu/episodes/'.$episode->id)->assertForbidden();
    }

    public function test_direct_url_manipulation_to_a_foreign_episode_is_rejected(): void
    {
        $episode = $this->makeEpisode();
        $otherOrganization = Organization::factory()->create();
        $foreignUser = $this->userWith(['icu.episode.view'], $otherOrganization);

        $this->actingAs($foreignUser)->get('/admin/icu/episodes/'.$episode->id)->assertForbidden();
    }

    public function test_api_denies_cross_hospital_transfer(): void
    {
        $episode = $this->makeEpisode();
        $otherOrganization = Organization::factory()->create();
        $foreignUser = $this->userWith(['icu.episode.transfer'], $otherOrganization);

        $this->actingAs($foreignUser)->postJson('/api/v1/icu/episodes/'.$episode->id.'/transfer', [
            'destination' => 'ward',
        ])->assertStatus(403);

        $this->assertNotSame('step_down', $episode->fresh()->status);
    }

    public function test_unauthorized_user_cannot_acknowledge_a_critical_alert(): void
    {
        $episode = $this->makeEpisode();
        $alert = app(\App\Services\Icu\IcuAlertService::class)->trigger($episode, 'heart_rate', 'high', '160', 'above 130');

        $noPermsUser = $this->userWith([]);

        $this->assertFalse($noPermsUser->can('acknowledge', $alert));
        $this->actingAs($noPermsUser)->post('/admin/icu/alerts/'.$alert->id.'/acknowledge')->assertForbidden();
    }
}
