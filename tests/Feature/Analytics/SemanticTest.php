<?php

namespace Tests\Feature\Analytics;

use App\Models\Analytics\AnalyticsDashboard;
use App\Models\Analytics\AnalyticsDqIssue;
use App\Models\Analytics\AnalyticsKpi;
use App\Models\Analytics\AnalyticsKpiVersion;
use App\Models\Billing\BillingInvoice;
use App\Models\Branch;
use App\Models\Encounter;
use App\Models\Organization;
use App\Services\Analytics\AnalyticsInstaller;
use App\Services\Analytics\DataQualityService;
use App\Services\Analytics\Kpi\DashboardService;
use App\Services\Analytics\Kpi\KpiService;
use App\Services\Analytics\Semantic\ScopeContext;
use App\Services\Analytics\Semantic\SemanticCatalog;
use App\Services\Analytics\Semantic\SemanticQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SemanticTest extends AnalyticsTestCase
{
    private function scope(?array $branches = null, bool $small = true): ScopeContext
    {
        return new ScopeContext($this->organization->id, $branches, $small);
    }

    private function q(array $q, ?ScopeContext $scope = null): array
    {
        return app(SemanticQuery::class)->run($q + ['from' => '2026-09-01', 'to' => '2026-09-30'], $scope ?? $this->scope());
    }

    private function revenue(): void
    {
        $this->bill(null, [['CONSULT', '1000.00'], ['LAB', '500.00']]);
        $this->bill(null, [['LAB', '300.00']]);
        $this->runLoads();
    }

    public function test_a_query_groups_and_totals_over_the_model(): void
    {
        $this->revenue();
        $r = $this->q(['fact' => 'revenue', 'measures' => ['net_revenue'], 'group_by' => ['category'], 'order_by' => 'net_revenue']);
        $this->assertSame(['CONSULT', 'LAB'], array_column($r['rows'], 'category'), 'biggest first');
        $this->assertSame([1000.0, 800.0], array_column($r['rows'], 'net_revenue'));
        $total = $this->q(['fact' => 'revenue', 'measures' => ['net_revenue', 'gross_revenue']]);
        $this->assertSame(1800.0, $total['rows'][0]['net_revenue']);
        $byDay = $this->q(['fact' => 'invoices', 'measures' => ['billed', 'collection_rate'], 'grain' => 'day']);
        $this->assertSame('2026-09-10', $byDay['rows'][0]['period']);
        $this->assertSame(1800.0, $byDay['rows'][0]['billed']);
        $this->assertSame(0.0, $byDay['rows'][0]['collection_rate']);
        $this->assertStringContainsString('select', $total['meta']['sql']);
        $this->assertStringContainsString('organization_id', $total['meta']['sql']);
    }

    public function test_a_branch_label_comes_from_the_dimension(): void
    {
        $this->revenue();
        $r = $this->q(['fact' => 'invoices', 'measures' => ['billed'], 'group_by' => ['branch']]);
        $this->assertSame($this->branch->name, $r['rows'][0]['branch']);
        $this->assertSame($this->branch->id, (int) $r['rows'][0]['branch_id']);
    }

    public function test_only_the_catalogue_can_be_asked_for_and_nothing_can_be_injected(): void
    {
        $bad = [
            ['fact' => 'nope', 'measures' => ['x']],
            ['fact' => 'revenue', 'measures' => ['password']],
            ['fact' => 'revenue', 'measures' => ['net_revenue'], 'group_by' => ['category; drop table users']],
            ['fact' => 'revenue', 'measures' => ['net_revenue'], 'filters' => [['dimension' => 'category', 'op' => 'like', 'value' => 'x']]],
            ['fact' => 'revenue', 'measures' => ['net_revenue'], 'filters' => [['dimension' => 'password', 'op' => '=', 'value' => 'x']]],
            ['fact' => 'revenue', 'measures' => ['net_revenue'], 'order_by' => 'net_revenue; select 1'],
            ['fact' => 'revenue', 'measures' => ['net_revenue'], 'grain' => 'hour'],
            ['fact' => 'revenue', 'measures' => ['net_revenue'], 'filters' => [['dimension' => 'category', 'op' => 'in', 'value' => []]]],
            ['fact' => 'ledger', 'measures' => ['cash_balance', 'ledger_revenue']],
            ['fact' => 'revenue', 'measures' => [['array']]],
        ];
        foreach ($bad as $q) {
            try {
                $this->q($q);
                $this->fail('accepted '.json_encode($q));
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
        $this->revenue();
        $hostile = $this->q(['fact' => 'revenue', 'measures' => ['net_revenue'], 'group_by' => ['category'], 'filters' => [['dimension' => 'category', 'op' => '=', 'value' => "x' or '1'='1"]]]);
        $this->assertSame([], $hostile['rows'], 'a value is a value, not SQL');
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('users'));
    }

    public function test_the_hospital_always_comes_from_the_user_not_the_query(): void
    {
        $this->revenue();
        $other = Organization::factory()->create();
        $r = $this->q(['fact' => 'revenue', 'measures' => ['net_revenue'], 'organization_id' => $other->id, 'filters' => [['dimension' => 'branch', 'op' => '=', 'value' => 999]]]);
        $this->assertSame(0.0, $r['rows'][0]['net_revenue'], 'an unknown branch gives nothing');
        $mine = $this->q(['fact' => 'revenue', 'measures' => ['net_revenue'], 'organization_id' => $other->id]);
        $this->assertSame(1800.0, $mine['rows'][0]['net_revenue'], 'still this hospital');
        $theirs = app(SemanticQuery::class)->run(['fact' => 'revenue', 'measures' => ['net_revenue'], 'from' => '2026-09-01', 'to' => '2026-09-30'], new ScopeContext($other->id, null, true));
        $this->assertSame(0.0, (float) ($theirs['rows'][0]['net_revenue'] ?? 0));
    }

    public function test_a_branch_manager_sees_only_their_branch(): void
    {
        $this->revenue();
        $second = Branch::factory()->create(['organization_id' => $this->organization->id]);
        $this->wh('fact_invoices')->insert(['invoice_id' => 9001, 'organization_id' => $this->organization->id, 'branch_id' => $second->id, 'date_key' => 20260910, 'payer_kind' => 'self', 'is_billed' => true, 'status' => 'finalized', 'subtotal' => 5000, 'grand_total' => 5000, 'paid' => 0, 'due' => 5000, 'discount' => 0, 'tax' => 0, 'rounding' => 0]);
        $all = $this->q(['fact' => 'invoices', 'measures' => ['billed']], $this->scope(null));
        $this->assertSame(6800.0, $all['rows'][0]['billed']);
        $mine = $this->q(['fact' => 'invoices', 'measures' => ['billed']], $this->scope([$this->branch->id]));
        $this->assertSame(1800.0, $mine['rows'][0]['billed'], 'the other branch is invisible');
        $theirs = $this->q(['fact' => 'invoices', 'measures' => ['billed'], 'group_by' => ['branch']], $this->scope([$second->id]));
        $this->assertCount(1, $theirs['rows']);
        $none = $this->q(['fact' => 'invoices', 'measures' => ['billed']], $this->scope([]));
        $this->assertSame(0.0, (float) ($none['rows'][0]['billed'] ?? 0), 'a user with no branch sees nothing');
        $kept = $this->q(['fact' => 'ledger', 'measures' => ['ledger_revenue']], $this->scope([$this->branch->id]));
        $this->assertSame([], $kept['rows'], 'a figure kept for the whole hospital is not shown for one branch');
        $this->assertNotEmpty($kept['meta']['note']);
    }

    public function test_small_numbers_are_hidden_without_the_sensitive_right(): void
    {
        $patient = $this->patient();
        foreach (range(1, 3) as $i) {
            Encounter::create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id, 'encounter_no' => "E-$i", 'encounter_type' => 'opd', 'provider_id' => $this->provider()->id, 'encounter_date' => '2026-09-08', 'status' => 'completed', 'source' => 'walk_in', 'created_by' => $this->superAdmin()->id]);
        }
        $this->runLoads();
        $seen = $this->q(['fact' => 'encounters', 'measures' => ['visits']], $this->scope(null, true));
        $this->assertSame(3, $seen['rows'][0]['visits']);
        $hidden = $this->q(['fact' => 'encounters', 'measures' => ['visits']], $this->scope(null, false));
        $this->assertNull($hidden['rows'][0]['visits']);
        $this->assertSame(1, $hidden['meta']['suppressed']);
    }

    public function test_kpis_have_versions_targets_and_a_previous_period(): void
    {
        app(AnalyticsInstaller::class)->install();
        $this->assertSame(25, AnalyticsKpi::query()->whereNull('organization_id')->count());
        $this->assertSame(0, app(KpiService::class)->installStandard(), 'installing again adds nothing');
        $this->revenue();
        $kpis = app(KpiService::class);
        $collection = AnalyticsKpi::query()->where('code', 'collection_rate')->first();
        $r = $kpis->evaluate($collection, $this->scope(), '2026-09-01', '2026-09-30');
        $this->assertSame(0.0, $r['value']);
        $this->assertSame(90.0, $r['target']);
        $this->assertSame('bad', $r['status']);
        $this->pay(BillingInvoice::query()->first(), '900.00');
        $this->runLoads();
        $r2 = $kpis->evaluate($collection, $this->scope(), '2026-09-01', '2026-09-30');
        $this->assertSame(50.0, $r2['value'], '900 of 1,800 billed');
        $revenue = AnalyticsKpi::query()->where('code', 'net_revenue')->first();
        $this->assertSame('neutral', $kpis->evaluate($revenue, $this->scope(), '2026-09-01', '2026-09-30')['status'], 'no target, no verdict');

        $by = $this->superAdmin();
        $kpis->setTarget($collection, $this->organization->id, '40', $by);
        $this->assertSame('good', $kpis->evaluate($collection, $this->scope(), '2026-09-01', '2026-09-30')['status'], 'this hospital set its own target');
        try {
            $kpis->save($this->organization->id, ['name' => 'x', 'domain' => 'finance', 'unit' => 'money', 'good_direction' => 'up', 'fact' => 'revenue', 'measure' => 'nope'], $by);
            $this->fail('a KPI outside the model');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
        $own = $kpis->save($this->organization->id, ['name' => 'Lab revenue', 'domain' => 'finance', 'unit' => 'money', 'good_direction' => 'up', 'fact' => 'revenue', 'measure' => 'net_revenue', 'filters' => [['dimension' => 'category', 'op' => '=', 'value' => 'LAB']]], $by);
        $this->assertSame(800.0, $kpis->evaluate($own, $this->scope(), '2026-09-01', '2026-09-30')['value']);
        $v2 = $kpis->save($this->organization->id, ['name' => 'Lab revenue', 'domain' => 'finance', 'unit' => 'money', 'good_direction' => 'up', 'fact' => 'revenue', 'measure' => 'gross_revenue', 'filters' => [['dimension' => 'category', 'op' => '=', 'value' => 'LAB']], 'note' => 'Use gross'], $by, $own);
        $this->assertSame(2, $v2->version);
        $this->assertSame([1, 2], AnalyticsKpiVersion::query()->where('kpi_id', $own->id)->orderBy('version')->pluck('version')->all(), 'the old definition is kept');
        $this->expectException(ValidationException::class);
        $kpis->save($this->organization->id, ['name' => 'Net revenue', 'domain' => 'finance', 'unit' => 'money', 'good_direction' => 'up', 'fact' => 'revenue', 'measure' => 'net_revenue'], $by, $revenue);
    }

    public function test_five_role_dashboards_render_and_a_broken_widget_does_not_break_the_page(): void
    {
        app(AnalyticsInstaller::class)->install();
        $this->revenue();
        $dash = app(DashboardService::class);
        $codes = AnalyticsDashboard::query()->whereNull('organization_id')->pluck('code')->all();
        sort($codes);
        $this->assertSame(['ceo', 'finance', 'medical_director', 'nursing', 'pharmacy'], $codes);
        foreach (AnalyticsDashboard::query()->get() as $d) {
            $widgets = $dash->render($d, $this->scope(), '2026-09-01', '2026-09-30');
            $this->assertNotEmpty($widgets);
            foreach ($widgets as $w) {
                $this->assertArrayNotHasKey('error', $w, $d->code.': '.($w['error'] ?? ''));
            }
        }
        $broken = AnalyticsDashboard::create(['organization_id' => $this->organization->id, 'code' => 'b1', 'name' => 'Broken', 'role_key' => 'custom', 'widgets' => [['type' => 'kpi', 'kpi' => 'no_such'], ['type' => 'kpi', 'kpi' => 'net_revenue']]]);
        $out = $dash->render($broken, $this->scope(), '2026-09-01', '2026-09-30');
        $this->assertArrayHasKey('error', $out[0]);
        $this->assertSame(1800.0, $out[1]['result']['value']);
    }

    public function test_data_quality_rules_open_an_issue_and_close_it_when_fixed(): void
    {
        app(AnalyticsInstaller::class)->install();
        $this->revenue();
        $dq = app(DataQualityService::class);
        $first = $dq->run();
        $this->assertSame(0, $first['failing'], json_encode(AnalyticsDqIssue::query()->get(['rule_code', 'detail'])));
        // break the warehouse: a billed invoice goes missing
        $this->wh('fact_invoices')->where('is_billed', true)->limit(1)->delete();
        $second = $dq->run();
        $this->assertGreaterThan(0, $second['opened']);
        $issue = AnalyticsDqIssue::query()->where('rule_code', 'billing_total_agrees')->where('status', 'open')->first();
        $this->assertNotNull($issue);
        $this->assertSame($this->organization->id, $issue->organization_id);
        $this->assertSame('error', $issue->severity);
        $dq->run();
        $this->assertSame(1, AnalyticsDqIssue::query()->where('rule_code', 'billing_total_agrees')->count(), 'running again does not pile up issues');
        DB::table('billing_invoices')->update(['updated_at' => now()->addMinute()]);
        $this->runLoads(['fact_invoices']);
        $third = $dq->run();
        $this->assertGreaterThanOrEqual(1, $third['closed']);
        $this->assertSame('resolved', AnalyticsDqIssue::query()->where('rule_code', 'billing_total_agrees')->value('status'));
    }

    public function test_the_catalogue_describes_every_fact_without_any_sql(): void
    {
        foreach (SemanticCatalog::describe() as $f) {
            $this->assertNotEmpty($f['measures']);
            $this->assertStringNotContainsString('select', strtolower(json_encode($f)));
        }
        $this->assertGreaterThanOrEqual(17, count(SemanticCatalog::facts()));
    }
}
