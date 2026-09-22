<?php

namespace Tests\Feature\System;

use App\Models\User;
use App\Services\SystemHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SystemHealthTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->user = User::factory()->create();

        foreach (['system.health.view', 'system.queue.view', 'system.scheduler.view'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $this->user->givePermissionTo($permission);
        }

        $this->actingAs($this->user);
    }

    public function test_health_page_loads_and_lists_every_component(): void
    {
        $response = $this->get('/admin/system/health');

        $response->assertOk();
        $response->assertViewHas('checks');

        $checks = $response->viewData('checks');
        foreach (['application', 'database', 'redis', 'queue', 'storage', 'mail', 'scheduler'] as $component) {
            $this->assertArrayHasKey($component, $checks);
            $this->assertContains($checks[$component]['status'], [
                SystemHealthService::HEALTHY,
                SystemHealthService::DEGRADED,
                SystemHealthService::DOWN,
            ]);
        }
    }

    public function test_a_down_dependency_does_not_crash_the_page(): void
    {
        // Redis is not running in this environment, so this exercises the real down-path.
        $response = $this->get('/admin/system/health');

        $response->assertOk();
        $checks = $response->viewData('checks');
        $this->assertSame(SystemHealthService::DOWN, $checks['redis']['status']);
    }

    public function test_database_check_is_healthy(): void
    {
        $checks = app(SystemHealthService::class)->check();

        $this->assertSame(SystemHealthService::HEALTHY, $checks['database']['status']);
    }

    public function test_storage_check_writes_reads_and_cleans_up(): void
    {
        $checks = app(SystemHealthService::class)->check();

        $this->assertSame(SystemHealthService::HEALTHY, $checks['storage']['status']);
    }

    public function test_queue_monitor_page_loads(): void
    {
        $this->get('/admin/system/queue')->assertOk();
    }

    public function test_scheduled_jobs_page_loads_and_lists_registered_tasks(): void
    {
        $response = $this->get('/admin/system/scheduled-jobs');

        $response->assertOk();
        $response->assertSee('sessions:prune-expired');
        $response->assertSee('audit:prune');
    }

    public function test_about_page_loads(): void
    {
        $this->get('/admin/system/about')->assertOk();
    }

    public function test_unauthorized_user_cannot_view_system_health(): void
    {
        $this->user->revokePermissionTo('system.health.view');

        $this->get('/admin/system/health')->assertForbidden();
    }
}
