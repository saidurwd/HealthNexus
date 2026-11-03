<?php

namespace Tests\Feature\Analytics;

use App\Models\Analytics\AnalyticsDashboard;
use App\Models\Analytics\AnalyticsDqIssue;
use App\Models\Analytics\AnalyticsForecast;
use App\Models\Analytics\AnalyticsKpi;
use App\Models\Analytics\AnalyticsReport;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Organization;
use App\Services\Analytics\AnalyticsInstaller;
use App\Services\Analytics\Insight\ForecastService;
use App\Services\Analytics\Reports\ReportService;
use App\Services\TenantContextResolver;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

class DeskTest extends AnalyticsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        app(AnalyticsInstaller::class)->install();
        $this->actingAs($this->superAdmin());
        session()->put(['tenant_organization_id' => $this->organization->id, 'tenant_branch_id' => $this->branch->id]);
        app(TenantContextResolver::class)->setOrganizationId($this->organization->id);
    }

    private function seedEverything(): array
    {
        $patient = $this->patient();
        $this->bill($patient, [['CONSULT', '1000.00'], ['LAB', '500.00']]);
        $this->labReport($patient);
        $this->runLoads();
        $report = app(ReportService::class)->save($this->organization->id, $this->superAdmin(), 'Revenue by service', 'x', ['fact' => 'revenue', 'measures' => ['net_revenue'], 'group_by' => ['category']], true);
        AnalyticsDqIssue::create(['organization_id' => $this->organization->id, 'rule_code' => 'visit_without_department', 'severity' => 'warning', 'detail' => '3 visits have no department.', 'failing' => 3, 'status' => 'open', 'first_seen_at' => now(), 'last_seen_at' => now()]);
        app(ForecastService::class)->run($this->organization->id, 'opd_visits');

        return compact('report');
    }

    public function test_every_screen_opens_with_data_in_it(): void
    {
        $x = $this->seedEverything();
        $d = AnalyticsDashboard::query()->where('code', 'ceo')->first();
        $k = AnalyticsKpi::query()->where('code', 'net_revenue')->first();
        $urls = ['admin.analytics.index' => [], 'admin.analytics.dashboards.show' => [$d->public_id], 'admin.analytics.dashboards.create' => [], 'admin.analytics.kpis' => [], 'admin.analytics.kpis.show' => [$k->public_id], 'admin.analytics.explore' => [], 'admin.analytics.reports' => [],
            'admin.analytics.reports.show' => [$x['report']->public_id], 'admin.analytics.schedules' => [], 'admin.analytics.quality' => [], 'admin.analytics.loads' => [], 'admin.analytics.research' => [], 'admin.analytics.benchmark' => [], 'admin.analytics.national' => [], 'admin.analytics.forecast' => [], 'admin.analytics.ask' => []];
        foreach ($urls as $name => $params) {
            $this->get(route($name, $params + ($name === 'admin.analytics.dashboards.show' || $name === 'admin.analytics.kpis' || $name === 'admin.analytics.kpis.show' ? ['from' => '2026-09-01', 'to' => '2026-09-30'] : [])))->assertOk();
        }
        foreach (AnalyticsDashboard::query()->whereNull('organization_id')->get() as $dash) {
            $this->get(route('admin.analytics.dashboards.show', [$dash->public_id, 'from' => '2026-09-01', 'to' => '2026-09-30']))->assertOk()->assertDontSee('KPI not found');
        }
        $this->get(route('admin.analytics.dashboards.show', [$d->public_id, 'from' => '2026-09-01', 'to' => '2026-09-30']))->assertSee('Net revenue')->assertSee('<svg', false);
        $this->get(route('admin.analytics.explore', ['fact' => 'revenue', 'measures' => ['net_revenue'], 'group_by' => ['category'], 'from' => '2026-09-01', 'to' => '2026-09-30']))->assertSee('CONSULT')->assertSee('How this was worked out');
        $this->get(route('admin.analytics.ask', ['q' => 'net revenue by category last month']))->assertSee('check the query');
        $this->get(route('admin.analytics.quality'))->assertSee('3 visits have no department.');
        $this->get(route('admin.analytics.loads'))->assertSee('fact_invoices');
    }

    public function test_a_chart_bar_drills_into_the_explorer(): void
    {
        $this->seedEverything();
        $d = AnalyticsDashboard::query()->create(['organization_id' => $this->organization->id, 'code' => 'dd', 'name' => 'Mine', 'role_key' => 'custom', 'shared' => true, 'widgets' => [['type' => 'chart', 'title' => 'By service', 'kind' => 'bar', 'query' => ['fact' => 'revenue', 'measures' => ['net_revenue'], 'group_by' => ['category']]]]]);
        $html = $this->get(route('admin.analytics.dashboards.show', [$d->public_id, 'from' => '2026-09-01', 'to' => '2026-09-30']))->assertOk()->getContent();
        $this->assertStringContainsString('fd%5B0%5D=category', $html);
        $this->assertStringContainsString('fv%5B0%5D=CONSULT', $html);
        $this->get(route('admin.analytics.explore', ['fact' => 'revenue', 'measures' => ['net_revenue'], 'grain' => 'day', 'fd' => ['category'], 'fo' => ['='], 'fv' => ['CONSULT'], 'from' => '2026-09-01', 'to' => '2026-09-30']))->assertOk()->assertSee('2026-09-10')->assertSee('1,000');
    }

    public function test_dashboards_reports_and_kpis_can_be_made_from_the_screens(): void
    {
        $this->seedEverything();
        $this->post(route('admin.analytics.dashboards.store'), ['name' => 'My board', 'shared' => 1, 'widgets' => [0 => ['type' => 'kpi', 'kpi' => 'net_revenue'], 1 => ['type' => 'chart', 'title' => 'By day', 'fact' => 'revenue', 'measure' => 'net_revenue', 'grain' => 'day', 'kind' => 'line'], 2 => ['type' => '']]])->assertRedirect();
        $this->assertSame(2, count(AnalyticsDashboard::query()->where('name', 'My board')->first()->widgets));
        $this->post(route('admin.analytics.dashboards.store'), ['name' => 'Bad', 'widgets' => [0 => ['type' => 'chart', 'fact' => 'revenue', 'measure' => 'salary']]])->assertSessionHasErrors();
        $this->post(route('admin.analytics.kpis.save'), ['name' => 'Lab share', 'domain' => 'finance', 'unit' => 'money', 'good_direction' => 'up', 'fact' => 'revenue', 'measure' => 'net_revenue', 'filter_dimension' => 'category', 'filter_value' => 'LAB'])->assertRedirect();
        $own = AnalyticsKpi::query()->where('organization_id', $this->organization->id)->first();
        $this->get(route('admin.analytics.kpis.show', [$own->public_id, 'from' => '2026-09-01', 'to' => '2026-09-30']))->assertOk()->assertSee('500');
        $this->post(route('admin.analytics.kpis.target', AnalyticsKpi::query()->where('code', 'collection_rate')->value('public_id')), ['target' => 70])->assertRedirect();
        $this->post(route('admin.analytics.reports.store'), ['name' => 'Saved', 'query' => json_encode(['fact' => 'invoices', 'measures' => ['billed'], 'grain' => 'month']), 'shared' => 1])->assertRedirect();
        $this->post(route('admin.analytics.reports.store'), ['name' => 'Bad', 'query' => '{"fact":"invoices","measures":["nope"]}'])->assertSessionHasErrors();
    }

    public function test_downloads_need_the_export_right_and_a_branch_user_sees_only_their_branch(): void
    {
        Mail::fake();
        $this->seedEverything();
        $second = Branch::factory()->create(['organization_id' => $this->organization->id]);
        $this->wh('fact_invoices')->insert(['invoice_id' => 9001, 'organization_id' => $this->organization->id, 'branch_id' => $second->id, 'date_key' => 20260910, 'payer_kind' => 'self', 'is_billed' => true, 'status' => 'finalized', 'subtotal' => 5000, 'grand_total' => 5000, 'paid' => 0, 'due' => 5000, 'discount' => 0, 'tax' => 0, 'rounding' => 0]);
        $viewer = $this->actor(['analytics.dashboard.view', 'analytics.query.run', 'analytics.report.view', 'analytics.kpi.view']);
        $viewer->branches()->sync([$this->branch->id => ['access_level' => 'manager', 'organization_id' => $this->organization->id]]);
        $this->actingAs($viewer);
        $url = route('admin.analytics.explore', ['fact' => 'invoices', 'measures' => ['billed'], 'from' => '2026-09-01', 'to' => '2026-09-30']);
        $this->get($url)->assertOk()->assertSee('1,500')->assertDontSee('6,500');
        $this->get($url.'&export=csv')->assertForbidden();
        $this->get(route('admin.analytics.reports.show', [AnalyticsReport::query()->first()->public_id, 'export' => 'csv']))->assertForbidden();
        $org = $this->actor(['analytics.dashboard.view', 'analytics.query.run', 'analytics.report.export', 'analytics.scope.organization']);
        $this->actingAs($org);
        $this->get($url)->assertSee('6,500');
        $this->get($url.'&export=csv')->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertNotNull(AuditLog::query()->where('action', 'ANALYTICS_EXPLORE_EXPORT')->first());
    }

    public function test_screens_need_the_right_and_never_show_another_hospitals_records(): void
    {
        $x = $this->seedEverything();
        $nobody = $this->actor(['patients.view']);
        $this->actingAs($nobody);
        foreach (['index', 'kpis', 'explore', 'reports', 'schedules', 'quality', 'loads', 'research', 'benchmark', 'national', 'forecast', 'ask'] as $n) {
            $this->get(route("admin.analytics.$n"))->assertForbidden();
        }
        $this->post(route('admin.analytics.loads.run'))->assertForbidden();
        $this->post(route('admin.analytics.loads.rebuild'), ['confirm' => 'REBUILD'])->assertForbidden();
        $this->actingAs($this->superAdmin());
        $other = Organization::factory()->create();
        $theirs = AnalyticsReport::create(['organization_id' => $other->id, 'name' => 'Theirs', 'query' => ['fact' => 'revenue', 'measures' => ['net_revenue']], 'shared' => true]);
        $theirDash = AnalyticsDashboard::create(['organization_id' => $other->id, 'code' => 't', 'name' => 'Theirs', 'role_key' => 'custom', 'shared' => true, 'widgets' => []]);
        $this->get(route('admin.analytics.reports.show', $theirs->public_id))->assertNotFound();
        $this->get(route('admin.analytics.dashboards.show', $theirDash->public_id))->assertNotFound();
        $mine = AnalyticsReport::create(['organization_id' => $this->organization->id, 'name' => 'Private', 'query' => ['fact' => 'revenue', 'measures' => ['net_revenue']], 'shared' => false, 'created_by' => $this->actor([])->id]);
        $this->get(route('admin.analytics.reports.show', $mine->public_id))->assertNotFound();
        $this->post(route('admin.analytics.loads.rebuild'), ['confirm' => 'yes'])->assertSessionHasErrors();
        $this->assertNotNull($x['report']->id);
    }

    public function test_commands_load_check_schedule_and_forecast(): void
    {
        Mail::fake();
        $this->bill(null, [['CONSULT', '1000.00']]);
        $this->assertSame(0, Artisan::call('analytics:load'));
        $this->assertStringContainsString('fact_invoices', Artisan::output());
        $this->assertSame(1, $this->wh('fact_invoices')->count());
        $this->assertSame(0, Artisan::call('analytics:check'));
        $this->assertSame(0, Artisan::call('analytics:run-schedules'));
        $this->assertSame(0, Artisan::call('analytics:forecast'));
        $this->assertSame(5, AnalyticsForecast::query()->count());
        $this->assertSame(0, Artisan::call('analytics:benchmark'));
        $this->assertStringContainsString('All KPIs', Artisan::output());
        $this->wh('fact_invoices')->delete();
        $this->assertSame(0, Artisan::call('analytics:rebuild', ['--force' => true]));
        $this->assertSame(1, $this->wh('fact_invoices')->count(), 'the warehouse was rebuilt from the records');
    }

    public function test_the_api_is_read_only_scoped_and_needs_the_hospital_header(): void
    {
        $this->seedEverything();
        foreach (['catalog', 'kpis'] as $p) {
            $this->getJson("/api/v1/analytics/$p")->assertUnauthorized();
        }
        $this->postJson('/api/v1/analytics/query', [])->assertUnauthorized();
        $user = $this->actor(['analytics.query.run', 'analytics.kpi.view', 'analytics.scope.organization']);
        $h = ['Authorization' => 'Bearer '.$user->createToken('t')->plainTextToken, 'X-Organization-Id' => (string) $this->organization->id];
        $this->getJson('/api/v1/analytics/catalog', $h)->assertOk()->assertJsonPath('success', true);
        $r = $this->postJson('/api/v1/analytics/query', ['fact' => 'revenue', 'measures' => ['net_revenue'], 'group_by' => ['category'], 'from' => '2026-09-01', 'to' => '2026-09-30'], $h)->assertOk();
        $this->assertSame(1000, (int) $r->json('data.0.net_revenue'));
        $this->assertArrayNotHasKey('sql', $r->json('meta'));
        $this->postJson('/api/v1/analytics/query', ['fact' => 'revenue', 'measures' => ['salary']], $h)->assertStatus(422);
        $this->postJson('/api/v1/analytics/query', ['fact' => 'revenue', 'measures' => ['net_revenue']], ['X-Organization-Id' => (string) Organization::factory()->create()->id] + $h)->assertForbidden();
        $weak = $this->actor(['analytics.dashboard.view']);
        $this->postJson('/api/v1/analytics/query', ['fact' => 'revenue', 'measures' => ['net_revenue']], ['Authorization' => 'Bearer '.$weak->createToken('t')->plainTextToken, 'X-Organization-Id' => (string) $this->organization->id])->assertForbidden();
        $this->getJson('/api/v1/analytics/kpis?from=2026-09-01&to=2026-09-30', $h)->assertOk();
    }
}
