<?php

namespace Tests\Feature\Rbac;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserRoleAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function actor(string $role): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::firstOrCreate(['name' => 'manage companies', 'guard_name' => 'web']);
        $r = Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        if ($role !== 'super_admin') {
            $r->givePermissionTo('manage companies');
        }
        $user = User::factory()->create();
        $user->assignRole($r);
        $company = Company::factory()->create();
        $user->companies()->attach($company->id, ['access_level' => 'admin']);

        return $user;
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'name' => 'Nina Nurse', 'email' => 'nina@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ], $extra);
    }

    public function test_admin_can_assign_roles_when_creating_and_editing_a_user(): void
    {
        Role::firstOrCreate(['name' => 'staff_nurse', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'charge_nurse', 'guard_name' => 'web']);
        $admin = $this->actor('hospital_admin');

        $this->actingAs($admin)->post('/admin/users', $this->payload(['roles' => ['staff_nurse']]))->assertRedirect();
        $nurse = User::where('email', 'nina@example.com')->firstOrFail();
        $this->assertTrue($nurse->hasRole('staff_nurse'));

        $this->actingAs($admin)->put('/admin/users/'.$nurse->id, [
            'name' => 'Nina Nurse', 'email' => 'nina@example.com', 'roles_present' => 1, 'roles' => ['charge_nurse'],
        ])->assertRedirect();

        $nurse->refresh();
        $this->assertTrue($nurse->hasRole('charge_nurse'));
        $this->assertFalse($nurse->hasRole('staff_nurse'));
    }

    public function test_non_super_admin_cannot_grant_super_admin(): void
    {
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $admin = $this->actor('hospital_admin');

        $this->actingAs($admin)->post('/admin/users', $this->payload(['roles' => ['super_admin']]))->assertSessionHasErrors('roles.0');

        $this->assertDatabaseMissing('users', ['email' => 'nina@example.com']);
    }

    public function test_editing_without_the_roles_field_leaves_roles_untouched_and_non_super_admin_cannot_strip_super_admin(): void
    {
        $superRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $target = User::factory()->create();
        $target->assignRole($superRole);
        $admin = $this->actor('hospital_admin');
        $base = ['name' => $target->name, 'email' => $target->email];

        $this->actingAs($admin)->put('/admin/users/'.$target->id, $base)->assertRedirect();
        $this->assertTrue($target->fresh()->hasRole('super_admin'));

        $this->actingAs($admin)->put('/admin/users/'.$target->id, $base + ['roles_present' => 1, 'roles' => []])->assertRedirect();
        $this->assertTrue($target->fresh()->hasRole('super_admin'), 'A non-super_admin must not be able to strip super_admin.');
    }

    public function test_forms_render_the_role_checkboxes(): void
    {
        Role::firstOrCreate(['name' => 'staff_nurse', 'guard_name' => 'web']);
        $admin = $this->actor('hospital_admin');
        $target = User::factory()->create();

        $this->actingAs($admin)->get('/admin/users/create')->assertOk()->assertSee('roles[]', false)->assertSee('Staff Nurse');
        $this->actingAs($admin)->get('/admin/users/'.$target->id.'/edit')->assertOk()->assertSee('roles_present', false);
    }
}
