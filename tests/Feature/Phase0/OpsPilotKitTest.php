<?php

namespace Tests\Feature\Phase0;

use App\Models\BackupRun;
use App\Models\Organization;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\Ops\BackupService;
use App\Services\Ops\OpsChecks;
use App\Services\Ops\ShellRunner;
use App\Services\Ops\TrainingMode;
use App\Services\TenantContextResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FakeShell implements ShellRunner
{
    public array $calls = [];

    public int $dumpExit = 0;

    public string $tables = "users\norganizations\n";

    public string $userCount = '2';

    public function run(string $command, array $env = [], ?int $timeout = 3600): array
    {
        $this->calls[] = ['command' => $command, 'env' => $env];

        if (str_contains($command, 'mysqldump')) {
            if ($this->dumpExit === 0) {
                // the real pipeline writes the file; emulate it at the path after the last '>'
                preg_match("/> '(.+)'$/", $command, $m);
                file_put_contents($m[1], str_repeat('x', 500));
            }

            return ['code' => $this->dumpExit, 'output' => '', 'error' => $this->dumpExit ? 'mysqldump: Access denied' : ''];
        }
        if (str_contains($command, 'SHOW TABLES')) {
            return ['code' => 0, 'output' => $this->tables, 'error' => ''];
        }
        if (str_contains($command, 'COUNT(*) FROM `users`')) {
            return ['code' => 0, 'output' => $this->userCount."\n", 'error' => ''];
        }
        if (str_contains($command, 'COUNT(*)')) {
            return ['code' => 0, 'output' => "1\n", 'error' => ''];
        }

        return ['code' => 0, 'output' => '', 'error' => ''];
    }
}

class TestableBackupService extends BackupService
{
    protected function connection(): array
    {
        return ['driver' => 'mysql', 'host' => 'db.test', 'port' => 3306, 'username' => 'hms', 'password' => 'S3cret!pw', 'database' => 'healthnexus'];
    }

    protected function tables(): array
    {
        return ['users', 'organizations'];
    }

    protected function tableCounts(): array
    {
        return ['users' => 2, 'organizations' => 1];
    }
}

/** H-15: backup + restore drill, monitoring baseline, readiness gate, training mode, downtime kit, quick-start cards, security headers. */
class OpsPilotKitTest extends TestCase
{
    use RefreshDatabase;

    private FakeShell $shell;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['ops.backup.disk' => 'local', 'ops.backup.passphrase' => 'correct horse']);
        $this->shell = new FakeShell;
        $this->app->instance(ShellRunner::class, $this->shell);
        $this->app->bind(BackupService::class, fn ($app) => new TestableBackupService($app->make(ShellRunner::class)));
    }

    public function test_backup_is_encrypted_checksummed_recorded_and_secrets_stay_off_the_command_line(): void
    {
        $run = app(BackupService::class)->backup();

        $this->assertSame('succeeded', $run->status);
        $this->assertTrue($run->encrypted);
        $this->assertStringEndsWith('.sql.gz.enc', $run->path);
        $this->assertSame(['users' => 2, 'organizations' => 1], $run->details['table_counts']);
        $this->assertSame(hash_file('sha256', Storage::disk('local')->path($run->path)), $run->checksum);

        $call = $this->shell->calls[0];
        $this->assertStringNotContainsString('S3cret!pw', $call['command']);
        $this->assertStringNotContainsString('correct horse', $call['command']);
        $this->assertSame('S3cret!pw', $call['env']['MYSQL_PWD']);
        $this->assertSame('correct horse', $call['env']['BACKUP_PASSPHRASE']);
        $this->assertStringContainsString('openssl enc -aes-256-cbc -pbkdf2', $call['command']);
        $this->assertStringContainsString('set -o pipefail', $call['command']);
    }

    public function test_a_failed_dump_is_recorded_and_leaves_no_file(): void
    {
        $this->shell->dumpExit = 2;

        try {
            app(BackupService::class)->backup();
            $this->fail('expected a failure');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Access denied', $e->getMessage());
        }

        $this->assertSame('failed', BackupRun::firstOrFail()->status);
        $this->assertSame([], Storage::disk('local')->allFiles('backups'));
    }

    public function test_restore_drill_passes_only_when_counts_match_and_must_be_signed_off_by_a_person(): void
    {
        $service = app(BackupService::class);
        $service->backup();

        $drill = $service->restoreDrill();
        $this->assertSame('succeeded', $drill->status);
        $this->assertSame(2, $drill->details['counts']['users']['restored']);
        $this->assertNull($drill->signed_off_at);
        $this->assertContains(true, array_map(fn ($c) => str_contains($c['command'], 'DROP DATABASE IF EXISTS `healthnexus_restore_drill`'), $this->shell->calls), 'scratch database is dropped');

        $this->shell->userCount = '1';   // restored data is missing a row
        $bad = $service->restoreDrill();
        $this->assertSame('failed', $bad->status);
        $this->assertStringContainsString('users: backup had 2 rows, restore has 1', $bad->error);

        $signed = $service->signOff($drill, 'Sultana Begum');
        $this->assertSame('Sultana Begum', $signed->signed_off_by);

        $this->expectException(\RuntimeException::class);
        $service->signOff($bad, 'Anyone');
    }

    public function test_restore_drill_refuses_a_backup_file_that_changed(): void
    {
        $service = app(BackupService::class);
        $run = $service->backup();
        file_put_contents(Storage::disk('local')->path($run->path), 'tampered');

        $drill = $service->restoreDrill();

        $this->assertSame('failed', $drill->status);
        $this->assertStringContainsString('checksum', $drill->error);
    }

    public function test_old_backup_files_are_pruned_but_their_history_stays(): void
    {
        $service = app(BackupService::class);
        $old = $service->backup();
        $old->update(['finished_at' => now()->subDays(30)]);

        $this->assertSame(1, $service->prune());
        Storage::disk('local')->assertMissing($old->path);
        $this->assertNull($old->fresh()->path);
        $this->assertNotNull($old->fresh());
    }

    public function test_monitoring_baseline_fails_loudly_and_clears_when_the_system_is_healthy(): void
    {
        $byName = fn () => collect(app(OpsChecks::class)->monitoring())->keyBy('name');

        $this->assertSame('fail', $byName()['Scheduler heartbeat']['status']);
        $this->assertSame('fail', $byName()['Last backup']['status']);
        $this->assertSame('fail', $byName()['Last restore drill']['status']);

        $this->artisan('ops:heartbeat');
        $service = app(BackupService::class);
        $service->backup();
        $service->signOff($service->restoreDrill(), 'Ops lead');

        $checks = $byName();
        $this->assertSame('ok', $checks['Scheduler heartbeat']['status']);
        $this->assertSame('ok', $checks['Last backup']['status']);
        $this->assertSame('ok', $checks['Last restore drill']['status']);
        $this->artisan('ops:check')->assertExitCode(0);

        DB::table('failed_jobs')->insert(['uuid' => 'u1', 'connection' => 'x', 'queue' => 'default', 'payload' => '{}', 'exception' => 'boom', 'failed_at' => now()]);
        $this->artisan('ops:check')->assertExitCode(1);
    }

    public function test_a_stale_heartbeat_and_an_unacknowledged_critical_alert_are_flagged(): void
    {
        Cache::forever(OpsChecks::HEARTBEAT_KEY, time() - 600);
        $organization = Organization::factory()->create();
        DB::table('clinical_alerts')->insert([
            'public_id' => (string) Str::ulid(), 'organization_id' => $organization->id, 'category' => 'critical_lab', 'severity' => 'critical',
            'message' => 'K 6.8', 'source_module' => 'lab', 'status' => 'open', 'ack_required' => true, 'raised_at' => now()->subMinutes(30), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $checks = collect(app(OpsChecks::class)->monitoring())->keyBy('name');

        $this->assertSame('fail', $checks['Scheduler heartbeat']['status']);
        $this->assertSame('fail', $checks['Critical alerts waiting']['status']);
    }

    public function test_pilot_readiness_says_no_go_on_a_fresh_development_machine(): void
    {
        $this->artisan('pilot:readiness')->expectsOutputToContain('NO-GO')->assertExitCode(1);
    }

    public function test_training_mode_shows_a_banner_blocks_mail_and_stamps_documents(): void
    {
        $training = Organization::factory()->create(['settings' => ['training' => true]]);
        $real = Organization::factory()->create();
        app(TenantContextResolver::class)->setOrganizationId($training->id);
        $this->assertTrue(app(TrainingMode::class)->active());

        Mail::fake();
        $user = User::factory()->create(['email' => 'nurse@example.test']);
        app(NotificationService::class)->send($user, 'critical_result', ['test' => 'K', 'patient' => 'P', 'value' => '6.8'], ['database', 'mail']);
        Mail::assertNothingQueued();
        $this->assertDatabaseHas('notifications', ['user_id' => $user->id]);

        app(TenantContextResolver::class)->setOrganizationId($real->id);
        $this->assertFalse(app(TrainingMode::class)->active());
    }

    public function test_training_banner_is_on_every_screen_of_a_training_organization(): void
    {
        $organization = Organization::factory()->create(['settings' => ['training' => true]]);
        $user = User::factory()->create();
        $user->organizations()->attach($organization->id, ['access_level' => 'admin']);
        Permission::firstOrCreate(['name' => 'downtime.view', 'guard_name' => 'web']);
        $user->givePermissionTo('downtime.view');
        $this->actingAs($user);
        session()->put('tenant_organization_id', $organization->id);
        app(TenantContextResolver::class)->setOrganizationId($organization->id);

        $this->get('/admin/downtime')->assertOk()->assertSee('data-training-banner', false);
    }

    public function test_downtime_forms_print_in_bangla_and_need_the_permission(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $user->organizations()->attach($organization->id, ['access_level' => 'admin']);
        $this->actingAs($user);
        session()->put('tenant_organization_id', $organization->id);
        app(TenantContextResolver::class)->setOrganizationId($organization->id);

        $this->get('/admin/downtime/forms/mar')->assertForbidden();

        Permission::firstOrCreate(['name' => 'downtime.view', 'guard_name' => 'web']);
        $user->givePermissionTo('downtime.view');

        $this->get('/admin/downtime/forms/mar?lang=bn')->assertOk()->assertSee('ঔষধ প্রদানের রেকর্ড')->assertSee('রোগীর নাম');
        $this->get('/admin/downtime/forms/prescription?lang=en+bn')->assertOk()->assertSee('প্রেসক্রিপশন (ডাউনটাইম) / Prescription (downtime)');
        $this->get('/admin/downtime/forms/nope')->assertNotFound();
        foreach (array_keys(config('downtime_forms')) as $form) {
            $this->get("/admin/downtime/forms/$form")->assertOk();
        }
    }

    public function test_every_downtime_form_label_has_a_bangla_translation(): void
    {
        $bn = json_decode(file_get_contents(lang_path('bn.json')), true);
        $missing = [];
        foreach (config('downtime_forms') as $form) {
            foreach (array_merge([$form['title']], $form['fields'], $form['table']['columns'] ?? [], $form['footer'] ?? []) as $label) {
                empty($bn[$label]) && $missing[] = $label;
            }
        }

        $this->assertSame([], array_values(array_unique($missing)));
    }

    public function test_quick_start_cards_exist_for_each_role_in_both_languages(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $user->organizations()->attach($organization->id, ['access_level' => 'staff']);
        $this->actingAs($user);
        session()->put('tenant_organization_id', $organization->id);
        app(TenantContextResolver::class)->setOrganizationId($organization->id);

        foreach (array_keys(trans('quickstart', [], 'en')) as $role) {
            $this->get("/admin/help/quick-start/$role")->assertOk()->assertSee('Steps')->assertSee('ধাপসমূহ');
        }
        $this->get('/admin/help/quick-start/janitor')->assertNotFound();
    }

    public function test_security_headers_are_sent_on_every_response(): void
    {
        $response = $this->get('/definitely/missing');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $secure = $this->get('https://localhost/definitely/missing');
        $secure->assertHeader('Strict-Transport-Security');
    }
}
