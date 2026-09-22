<?php

namespace Tests\Feature\Console;

use App\Console\Commands\RefreshSystemStatistics;
use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchedulerCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_prune_expired_sessions_deletes_only_stale_rows(): void
    {
        // The test environment defaults SESSION_DRIVER to "array" so real HTTP tests don't
        // touch the sessions table; force "database" here since that's the branch under test.
        config(['session.driver' => 'database']);

        DB::table('sessions')->insert([
            ['id' => 'expired', 'payload' => 'x', 'last_activity' => now()->subDays(5)->getTimestamp()],
            ['id' => 'fresh', 'payload' => 'x', 'last_activity' => now()->getTimestamp()],
        ]);

        $this->artisan('sessions:prune-expired')->assertSuccessful();

        $this->assertDatabaseMissing('sessions', ['id' => 'expired']);
        $this->assertDatabaseHas('sessions', ['id' => 'fresh']);
    }

    public function test_prune_audit_logs_respects_retention_setting(): void
    {
        // Eloquent auto-manages timestamps on create(), overwriting any created_at passed in —
        // insert directly so the "old" row is genuinely backdated.
        DB::table('audit_logs')->insert([
            'action' => 'OLD', 'created_at' => now()->subDays(400), 'updated_at' => now()->subDays(400),
        ]);
        $recent = AuditLog::create(['action' => 'RECENT']);

        DB::table('activity_logs')->insert([
            'action' => 'OLD_ACTIVITY', 'created_at' => now()->subDays(400), 'updated_at' => now()->subDays(400),
        ]);

        $this->artisan('audit:prune')->assertSuccessful();

        $this->assertDatabaseMissing('audit_logs', ['action' => 'OLD']);
        $this->assertDatabaseHas('audit_logs', ['id' => $recent->id]);
        $this->assertDatabaseMissing('activity_logs', ['action' => 'OLD_ACTIVITY']);
    }

    public function test_refresh_system_statistics_populates_cache(): void
    {
        User::factory()->count(3)->create();

        Cache::forget(RefreshSystemStatistics::CACHE_KEY);

        $this->artisan('system:refresh-statistics')->assertSuccessful();

        $stats = Cache::get(RefreshSystemStatistics::CACHE_KEY);

        $this->assertNotNull($stats);
        $this->assertSame(3, $stats['total_users']);
        $this->assertArrayHasKey('generated_at', $stats);
    }
}
