<?php

namespace Tests\Feature\Phase0;

use App\Jobs\Reports\GenerateReportExport;
use App\Models\Branch;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\ReportExport;
use App\Models\User;
use App\Services\Reports\ReportExportService;
use App\Services\TenantContextResolver;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use OpenSpout\Reader\XLSX\Reader;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/** H-07: queued, scoped, audited, masked, expiring exports. */
class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->organization = Organization::factory()->create();
        $branch = Branch::factory()->create(['organization_id' => $this->organization->id]);
        $this->user = $this->member($this->organization, ['patients.export']);
        $this->actingAs($this->user);
        app(TenantContextResolver::class)->setOrganizationId($this->organization->id);
        app(TenantContextResolver::class)->setBranchId($branch->id);
    }

    private function member(Organization $organization, array $permissions): User
    {
        $user = User::factory()->create(['name' => 'Rahim Uddin']);
        $user->organizations()->attach($organization->id, ['access_level' => 'staff']);
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $user->givePermissionTo($permission);
        }

        return $user;
    }

    private function export(array $filters = [], ?User $as = null): ReportExport
    {
        return app(ReportExportService::class)->request($as ?? $this->user, 'patients.list', 'csv', $filters)->refresh();
    }

    public function test_export_runs_through_the_queue_and_completes_with_an_expiry(): void
    {
        Patient::factory()->count(3)->create(['organization_id' => $this->organization->id]);

        $export = $this->export();

        $this->assertSame('completed', $export->status);
        $this->assertSame(3, $export->row_count);
        $this->assertTrue($export->expires_at->isFuture());
        Storage::disk('local')->assertExists($export->path);
        $this->assertDatabaseHas('audit_logs', ['action' => 'EXPORT_REQUESTED']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'EXPORT_COMPLETED']);
    }

    public function test_large_exports_do_not_block_the_request(): void
    {
        Queue::fake();

        $export = app(ReportExportService::class)->request($this->user, 'patients.list', 'csv');

        $this->assertSame('queued', $export->status);
        Queue::assertPushed(GenerateReportExport::class);
    }

    public function test_file_carries_a_watermark_and_never_includes_another_organizations_rows(): void
    {
        Patient::factory()->create(['organization_id' => $this->organization->id, 'first_name' => 'Karim']);
        Patient::factory()->create(['organization_id' => Organization::factory()->create()->id, 'first_name' => 'Outsider']);

        $csv = Storage::disk('local')->get($this->export()->path);

        $this->assertStringContainsString('exported by Rahim Uddin', $csv);
        $this->assertStringContainsString('CONFIDENTIAL', $csv);
        $this->assertStringContainsString('Karim', $csv);
        $this->assertStringNotContainsString('Outsider', $csv);
    }

    public function test_sensitive_columns_are_masked_unless_the_user_has_the_sensitive_permission(): void
    {
        Patient::factory()->create(['organization_id' => $this->organization->id, 'phone' => '01711000111', 'national_identifier' => '1990123456789']);

        $masked = Storage::disk('local')->get($this->export()->path);
        $this->assertStringNotContainsString('01711000111', $masked);
        $this->assertStringContainsString('***', $masked);

        $privileged = $this->member($this->organization, ['patients.export', 'patients.export.sensitive']);
        $clear = Storage::disk('local')->get($this->export(as: $privileged)->path);
        $this->assertStringContainsString('01711000111', $clear);
    }

    public function test_spreadsheet_formulas_are_neutralised(): void
    {
        Patient::factory()->create(['organization_id' => $this->organization->id, 'first_name' => '=HYPERLINK("http://x")']);

        $this->assertStringContainsString("'=HYPERLINK", Storage::disk('local')->get($this->export()->path));
    }

    public function test_permission_format_and_filters_are_enforced_before_anything_is_queued(): void
    {
        Queue::fake();
        $service = app(ReportExportService::class);
        $nobody = $this->member($this->organization, []);

        foreach ([
            [fn () => $service->request($nobody, 'patients.list', 'csv'), AuthorizationException::class],
            [fn () => $service->request($this->user, 'patients.list', 'pdf'), ValidationException::class],
            [fn () => $service->request($this->user, 'nope.list', 'csv'), ValidationException::class],
            [fn () => $service->request($this->user, 'patients.list', 'csv', ['registered_from' => 'not-a-date']), ValidationException::class],
        ] as [$call, $exception]) {
            try {
                $call();
                $this->fail("expected $exception");
            } catch (\Throwable $e) {
                $this->assertInstanceOf($exception, $e);
            }
        }

        $this->assertSame(0, ReportExport::count());
    }

    public function test_only_the_owner_or_an_export_manager_can_download_and_expired_files_are_gone(): void
    {
        Patient::factory()->create(['organization_id' => $this->organization->id]);
        $export = $this->export();
        $service = app(ReportExportService::class);

        $this->assertSame(200, $service->download($export, $this->user)->getStatusCode());

        try {
            $service->download($export, $this->member($this->organization, []));
            $this->fail('another user must be refused');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }

        $this->travel(25)->hours();
        $this->assertSame(1, $service->pruneExpired());
        $this->assertSame('expired', $export->fresh()->status);
        Storage::disk('local')->assertMissing($export->path);

        $this->expectException(HttpException::class);
        $service->download($export->fresh(), $this->user);
    }

    public function test_api_hides_other_organizations_exports_behind_a_404(): void
    {
        $export = $this->export();
        $otherOrg = Organization::factory()->create();
        $outsider = $this->member($otherOrg, ['patients.export']);
        app(TenantContextResolver::class)->setOrganizationId($otherOrg->id);

        $this->actingAs($outsider, 'sanctum')->withHeader('X-Organization-Id', (string) $otherOrg->id)
            ->getJson("/api/v1/exports/{$export->public_id}")->assertNotFound();
    }

    public function test_xlsx_export_has_the_watermark_masks_sensitive_columns_and_keeps_text_as_text(): void
    {
        Patient::factory()->create(['organization_id' => $this->organization->id, 'first_name' => '=SUM(A1)', 'middle_name' => null, 'last_name' => 'Test', 'phone' => '01711000111']);

        $export = app(ReportExportService::class)->request($this->user, 'patients.list', 'xlsx')->refresh();

        $this->assertSame('completed', $export->status);
        $reader = new Reader;
        $reader->open(Storage::disk('local')->path($export->path));
        $cells = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells[] = array_map(fn ($c) => $c->getValue(), $row->cells);
            }
        }
        $reader->close();

        $flat = json_encode($cells, JSON_UNESCAPED_UNICODE);
        $this->assertStringContainsString('exported by Rahim Uddin', $flat);
        $this->assertStringContainsString('=SUM(A1) Test', $flat);
        $this->assertStringNotContainsString('FormulaCell', $flat);
        $this->assertStringNotContainsString('01711000111', $flat);
        $this->assertStringContainsString('***', $flat);
    }
}
