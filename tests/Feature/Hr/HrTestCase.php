<?php

namespace Tests\Feature\Hr;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Hr\HrEmployee;
use App\Models\Hr\HrShift;
use App\Models\Organization;
use App\Models\User;
use App\Services\Hr\CompensationService;
use App\Services\Hr\EmployeeService;
use App\Services\Hr\LeaveBalanceService;
use App\Services\Hr\RosterService;
use App\Services\TenantContextResolver;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

abstract class HrTestCase extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected Branch $branch;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-10 12:00:00'); // a Thursday; August is a finished month
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->organization = Organization::factory()->create();
        $this->branch = Branch::factory()->create(['organization_id' => $this->organization->id]);
        $this->user = $this->staff(['super_admin']);
        $this->actingAs($this->user);
        $this->context();
        app(LeaveBalanceService::class)->seedTypes($this->organization->id);
        app(CompensationService::class)->seedComponents($this->organization->id);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function context(?Organization $organization = null, ?Branch $branch = null): void
    {
        $organization ??= $this->organization;
        $branch ??= $this->branch;
        session()->put(['tenant_organization_id' => $organization->id, 'tenant_branch_id' => $branch->id]);
        app(TenantContextResolver::class)->setOrganizationId($organization->id);
        app(TenantContextResolver::class)->setBranchId($branch->id);
    }

    /** @param  list<string>  $roles */
    protected function staff(array $roles = [], ?Organization $organization = null, ?Branch $branch = null): User
    {
        $organization ??= $this->organization;
        $branch ??= $this->branch;
        $user = User::factory()->create();
        $user->organizations()->attach($organization->id, ['access_level' => 'admin']);
        $user->branches()->attach($branch->id, ['access_level' => 'manager', 'organization_id' => $organization->id]);
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
            $user->assignRole($role);
        }

        return $user;
    }

    /** A user who holds exactly these permissions. @param  list<string>  $permissions */
    protected function actor(array $permissions): User
    {
        $user = $this->staff();
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $user->givePermissionTo($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user->refresh();
    }

    protected function department(): Department
    {
        return Department::factory()->create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id]);
    }

    /** An active employee who joined on the given date. @param  array<string, mixed>  $o */
    protected function employee(array $o = []): HrEmployee
    {
        $employees = app(EmployeeService::class);
        $employee = $employees->create($this->user, $this->organization->id, $o + ['first_name' => fake()->firstName(), 'last_name' => fake()->lastName(), 'joined_on' => '2025-01-01', 'gender' => 'male', 'branch_id' => $this->branch->id]);
        $employee->update(['status' => 'active']);

        return $employee->refresh();
    }

    protected function shift(string $code = 'DAY', string $start = '08:00', string $end = '16:00', int $break = 0, int $grace = 10): HrShift
    {
        return HrShift::query()->where(['organization_id' => $this->organization->id, 'code' => $code])->first()
            ?? app(RosterService::class)->createShift($this->organization->id, $code, $code.' shift', $start, $end, $break, $grace);
    }

    protected function date(string $d): CarbonImmutable
    {
        return CarbonImmutable::parse($d);
    }

    /** Salary: basic 30,000 plus 50% house rent, medical 1,500, PF 10% employee and employer. */
    protected function salary(HrEmployee $employee, string $from = '2025-01-01', string $basic = '30000'): void
    {
        app(CompensationService::class)->newStructure($employee, $this->date($from), [
            ['component' => 'BASIC', 'amount' => $basic], ['component' => 'HRENT', 'percent' => 50], ['component' => 'MEDICAL', 'amount' => 1500],
            ['component' => 'PF', 'percent' => 10], ['component' => 'PFER', 'percent' => 10],
        ], $this->user);
    }
}
