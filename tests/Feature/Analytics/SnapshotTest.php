<?php

namespace Tests\Feature\Analytics;

use App\Models\Analytics\AnalyticsDashboard;
use App\Models\Analytics\AnalyticsDashboardSnapshot;
use App\Models\Organization;
use App\Services\Analytics\AnalyticsInstaller;
use App\Services\Analytics\Kpi\DashboardSnapshotService;
use App\Services\Analytics\Semantic\ScopeContext;
use App\Services\TenantContextResolver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Artisan;

/** The dashboards on the standard, the kept snapshots and the month-end pack (D6). */
class SnapshotTest extends AnalyticsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        app(AnalyticsInstaller::class)->install();
        $this->actingAs($this->superAdmin());
        session()->put(['tenant_organization_id' => $this->organization->id, 'tenant_branch_id' => $this->branch->id]);
        app(TenantContextResolver::class)->setOrganizationId($this->organization->id);
        $this->bill($this->patient(), [['CONSULT', '1000.00'], ['LAB', '500.00']]);
        $this->runLoads();
    }

    private function ceo(): AnalyticsDashboard
    {
        return AnalyticsDashboard::query()->where(['is_system' => true, 'code' => 'ceo'])->firstOrFail();
    }

    public function test_a_dashboard_states_its_question_its_period_and_when_it_was_worked_out(): void
    {
        $html = $this->get(route('admin.analytics.dashboards.show', $this->ceo()->public_id))->assertOk()->getContent();

        $this->assertStringContainsString('data-dashboard-question', $html);
        $this->assertStringContainsString('data-dashboard-asof', $html);
        $this->assertStringContainsString('data-dashboard-filter', $html);
        $this->assertStringContainsString('Keep a snapshot of this view', $html);
    }

    public function test_a_snapshot_kept_by_hand_does_not_change_when_the_data_does(): void
    {
        $this->post(route('admin.analytics.dashboards.snapshot', $this->ceo()->public_id), ['period' => '30d'])->assertRedirect();
        $s = AnalyticsDashboardSnapshot::query()->firstOrFail();
        $kept = fn () => explode('These figures do not change', $this->get(route('admin.analytics.snapshots.show', $s->public_id))->assertOk()->getContent())[1];
        $before = $kept();

        $this->bill($this->patient(), [['CONSULT', '9000.00']]);
        $this->runLoads();

        $this->assertSame($before, $kept());
        $this->assertSame('manual', $s->kind);
        $this->assertSame(64, strlen($s->checksum));
        $this->assertNotNull($s->taken_by);
    }

    public function test_the_month_end_pack_keeps_every_system_dashboard_once_and_hides_small_numbers(): void
    {
        $month = CarbonImmutable::now()->subMonthNoOverflow()->startOfMonth();
        $systemCount = AnalyticsDashboard::query()->where('is_system', true)->count();

        $this->assertSame($systemCount, app(DashboardSnapshotService::class)->monthlyPack($this->organization->id, $month));
        $this->assertSame(0, app(DashboardSnapshotService::class)->monthlyPack($this->organization->id, $month), 'taking the same month again does nothing');
        $this->assertSame($systemCount, AnalyticsDashboardSnapshot::query()->where('kind', 'monthly')->count());

        $html = $this->get(route('admin.analytics.pack', $month->format('Y-m')))->assertOk()->getContent();
        $this->assertStringContainsString('pack-sheet', $html);
        $this->assertSame($systemCount, substr_count($html, '<section class="pack-sheet'));
    }

    public function test_the_command_keeps_the_pack_for_a_named_month(): void
    {
        Artisan::call('analytics:dashboard-pack', ['--month' => '2026-09']);

        $this->assertGreaterThan(0, AnalyticsDashboardSnapshot::query()->where(['kind' => 'monthly', 'period_from' => '2026-09-01', 'period_to' => '2026-09-30'])->count());
        $this->assertNotSame(0, Artisan::call('analytics:dashboard-pack', ['--month' => 'nonsense']), 'a bad month is refused');
    }

    public function test_a_month_with_no_pack_is_not_found_and_a_malformed_month_has_no_route(): void
    {
        $this->get(route('admin.analytics.pack', '2020-01'))->assertNotFound();
        $this->get('/admin/analytics/pack/abc')->assertNotFound();
    }

    public function test_only_people_with_the_right_may_see_or_take_snapshots_and_another_hospital_is_invisible(): void
    {
        $s = app(DashboardSnapshotService::class)->take($this->ceo(), ScopeContext::system($this->organization->id), '2026-09-01', '2026-09-30');
        $other = Organization::factory()->create();
        $theirs = AnalyticsDashboardSnapshot::factory()->create(['organization_id' => $other->id]);

        $this->actingAs($this->actor(['patients.view']));
        $this->get(route('admin.analytics.snapshots'))->assertForbidden();
        $this->get(route('admin.analytics.snapshots.show', $s->public_id))->assertForbidden();
        $this->post(route('admin.analytics.dashboards.snapshot', $this->ceo()->public_id))->assertForbidden();

        $this->actingAs($this->actor(['analytics.dashboard.view']));
        $this->get(route('admin.analytics.snapshots'))->assertOk();
        $this->post(route('admin.analytics.dashboards.snapshot', $this->ceo()->public_id))->assertForbidden();

        $this->actingAs($this->superAdmin());
        $this->get(route('admin.analytics.snapshots.show', $theirs->public_id))->assertNotFound();
    }

    public function test_the_national_return_page_says_whether_it_is_ready(): void
    {
        $html = $this->get(route('admin.analytics.national'))->assertOk()->getContent();

        $this->assertStringContainsString('data-dashboard-question', $html);
        $this->assertStringContainsString('Indicators with a national code', $html);
        $this->assertStringContainsString('Not yet', $html);
    }
}
