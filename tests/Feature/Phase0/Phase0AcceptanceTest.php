<?php

namespace Tests\Feature\Phase0;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Phase0AcceptanceTest extends TestCase
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
        Permission::create(['name' => 'manage branches', 'guard_name' => 'web']);
        Permission::create(['name' => 'manage departments', 'guard_name' => 'web']);
        Permission::create(['name' => 'manage users', 'guard_name' => 'web']);

        $this->user->givePermissionTo('manage companies');
        $this->user->givePermissionTo('manage branches');
        $this->user->givePermissionTo('manage departments');
        $this->user->givePermissionTo('manage users');

        $this->actingAs($this->user);
    }

    public function test_user_can_log_in(): void
    {
        $response = $this->post('/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/login/context');
        $this->assertAuthenticated();
    }

    public function test_user_can_log_out(): void
    {
        $this->post('/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);

        $response = $this->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_mfa_architecture_exists(): void
    {
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
        ]);

        $this->assertTrue(\Schema::hasColumn('users', 'google2fa_secret'));
        $this->assertTrue(\Schema::hasColumn('users', 'mfa_enabled'));
        $this->assertTrue(\Schema::hasColumn('users', 'mfa_confirmed_at'));
    }

    public function test_admin_can_create_company(): void
    {
        $response = $this->post('/admin/companies', [
            'name' => 'Test Hospital',
            'code' => 'TEST01',
            'slug' => 'test-hospital',
            'email' => 'info@testhospital.com',
            'phone' => '1234567890',
            'address' => '123 Test Street',
        ]);

        $response->assertRedirect('/admin/companies');
        $this->assertDatabaseHas('companies', [
            'name' => 'Test Hospital',
            'code' => 'TEST01',
        ]);
    }

    public function test_admin_can_create_branch(): void
    {
        $response = $this->post('/admin/companies/'.$this->company->id.'/branches', [
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
            'code' => 'MAIN',
            'slug' => 'main-branch',
            'email' => 'main@test.com',
            'phone' => '1234567890',
            'address' => '456 Main Street',
        ]);

        $response->assertRedirect('/admin/companies/'.$this->company->id.'/branches');
        $this->assertDatabaseHas('branches', [
            'name' => 'Main Branch',
            'code' => 'MAIN',
            'company_id' => $this->company->id,
        ]);
    }

    public function test_admin_can_create_department(): void
    {
        $response = $this->post('/admin/companies/'.$this->company->id.'/branches/'.$this->branch->id.'/departments', [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Cardiology',
            'code' => 'CARD',
            'description' => 'Cardiology Department',
        ]);

        $response->assertRedirect('/admin/companies/'.$this->company->id.'/branches/'.$this->branch->id.'/departments');
        $this->assertDatabaseHas('departments', [
            'name' => 'Cardiology',
            'code' => 'CARD',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
        ]);
    }

    public function test_admin_can_create_user(): void
    {
        $response = $this->post('/admin/users', [
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone' => '1234567890',
        ]);

        $response->assertRedirect('/admin/users');
        $this->assertDatabaseHas('users', [
            'email' => 'testuser@example.com',
        ]);
    }

    public function test_admin_can_assign_roles(): void
    {
        $role = Role::create(['name' => 'doctor', 'guard_name' => 'web']);

        $this->user->assignRole($role);

        $this->assertTrue($this->user->hasRole('doctor'));
    }

    public function test_permissions_work(): void
    {
        $this->assertTrue($this->user->hasPermissionTo('manage companies'));
        $this->assertFalse($this->user->hasAnyPermission(['nonexistent.permission']));
    }

    public function test_unauthorized_users_are_blocked(): void
    {
        $unauthorizedUser = User::factory()->create();
        $this->actingAs($unauthorizedUser);

        $response = $this->get('/admin/companies');

        $response->assertStatus(403);
    }

    public function test_audit_logs_are_generated(): void
    {
        $this->post('/admin/companies', [
            'name' => 'Audit Test Hospital',
            'code' => 'AUDIT01',
            'slug' => 'audit-test',
            'email' => 'audit@test.com',
            'phone' => '1234567890',
            'address' => '789 Audit Street',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'CREATE',
            'model_type' => \App\Models\Company::class,
        ]);
    }

    public function test_api_authentication_works(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->get('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'email' => $this->user->email,
                    ],
                ],
            ]);
    }
}
