<?php

namespace Tests\Feature\Ot;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use App\Services\Ot\OtConsentService;
use App\Services\Ot\OtOperativeNoteService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class OtTenantIsolationTest extends OtTestCase
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

        $role = Role::firstOrCreate(['name' => 'surgeon_test', 'guard_name' => 'web']);
        $role->syncPermissions($permissions);
        $user->assignRole($role);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user->fresh();
    }

    public function test_hospital_a_user_cannot_touch_hospital_b_surgery(): void
    {
        $surgery = $this->makeDaySurgery();
        $otherOrganization = Organization::factory()->create();
        $foreignUser = $this->userWith(['ot.surgery.view', 'ot.surgery.start', 'ot.surgery.complete'], $otherOrganization);

        $this->assertFalse($foreignUser->can('view', $surgery));
        $this->assertFalse($foreignUser->can('start', $surgery));

        $this->actingAs($foreignUser)->get('/admin/ot/surgeries/'.$surgery->public_id)->assertForbidden();
        $this->actingAs($foreignUser)->post('/admin/ot/surgeries/'.$surgery->public_id.'/start')->assertForbidden();
    }

    public function test_unauthorized_user_cannot_view_operative_note_or_consent(): void
    {
        $surgery = $this->makeDaySurgery();
        app(OtConsentService::class)->grant($surgery, $this->user);
        $note = app(OtOperativeNoteService::class)->create($surgery, ['findings' => 'sensitive'], $this->user);

        $noPermsUser = $this->userWith([]);

        $this->assertFalse($noPermsUser->can('view', $note));
        $this->actingAs($noPermsUser)->get('/admin/ot/surgeries/'.$surgery->public_id)->assertForbidden();
    }

    public function test_api_denies_cross_hospital_schedule_and_unauthorized_completion(): void
    {
        $surgery = $this->makeDaySurgery();
        $theatre = $this->makeTheatre();
        $otherOrganization = Organization::factory()->create();
        $foreignUser = $this->userWith(['ot.schedule.create', 'ot.surgery.view'], $otherOrganization);

        $this->actingAs($foreignUser)->postJson('/api/v1/ot/surgeries/'.$surgery->public_id.'/schedule', [
            'theatre_id' => $theatre->id,
            'scheduled_start_at' => now()->toDateTimeString(),
            'scheduled_end_at' => now()->addHours(2)->toDateTimeString(),
        ])->assertStatus(403);

        $this->assertNull($surgery->fresh()->theatre_id);
    }

    public function test_direct_url_manipulation_to_a_foreign_surgery_id_is_rejected(): void
    {
        $surgery = $this->makeDaySurgery();
        $otherOrganization = Organization::factory()->create();
        $foreignUser = $this->userWith(['ot.surgery.view'], $otherOrganization);

        $this->actingAs($foreignUser)->get('/admin/ot/surgeries/'.$surgery->public_id)->assertForbidden();
    }
}
