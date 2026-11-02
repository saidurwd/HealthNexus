<?php

namespace Tests\Feature\Dashboard;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use App\Services\TenantContextResolver;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * A hospital, a clock fixed on a Thursday morning in Dhaka, people who hold exactly the rights a test names, and a way to put a bare row into any table. The tests read counts, so foreign keys are deferred on SQLite and
 * a row carries only what the count looks at.
 */
abstract class FrontDoorTestCase extends TestCase
{
    use DashboardAcceptance, RefreshDatabase;

    protected Organization $organization;

    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Cache::flush();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-12 10:00:00', 'Asia/Dhaka')); // a Thursday
        Carbon::setTestNow(Carbon::parse('2026-11-12 10:00:00', 'Asia/Dhaka'));
        DB::getDriverName() === 'sqlite' && DB::statement('PRAGMA defer_foreign_keys = ON'); // checked only at commit, and the test rolls back
        $this->organization = Organization::factory()->create();
        $this->branch = Branch::factory()->create(['organization_id' => $this->organization->id]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @param list<string> $permissions */
    protected function actor(array $permissions): User
    {
        $user = User::factory()->create();
        $user->organizations()->attach($this->organization->id, ['access_level' => 'admin']);
        $user->branches()->attach($this->branch->id, ['access_level' => 'manager', 'organization_id' => $this->organization->id]);
        foreach ($permissions as $p) {
            Permission::findOrCreate($p, 'web');
            $user->givePermissionTo($p);
        }

        return $user;
    }

    protected function as(User $user): static
    {
        $this->actingAs($user);
        session()->put(['tenant_organization_id' => $this->organization->id, 'tenant_branch_id' => $this->branch->id]);
        app(TenantContextResolver::class)->setOrganizationId($this->organization->id);
        app(TenantContextResolver::class)->setBranchId($this->branch->id);

        return $this;
    }

    /** A bare row with this hospital and branch unless the test says otherwise. */
    protected function row(string $table, array $attrs = []): int
    {
        $base = ['organization_id' => $this->organization->id, 'created_at' => now(), 'updated_at' => now()];
        $columns = DB::getSchemaBuilder()->getColumnListing($table);
        in_array('branch_id', $columns, true) && $base['branch_id'] = $this->branch->id;
        in_array('public_id', $columns, true) && $base['public_id'] = (string) Str::ulid();
        $row = array_filter(array_merge($base, $attrs), fn ($v, $k) => in_array($k, $columns, true), ARRAY_FILTER_USE_BOTH);

        return DB::table($table)->insertGetId($row);
    }

    protected function otherHospital(): Organization
    {
        return Organization::factory()->create();
    }
}
