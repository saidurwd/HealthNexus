<?php

namespace Tests\Feature\Analytics;

use App\Contracts\Documents\PdfRenderer;
use App\Models\Analytics\AnalyticsDataset;
use App\Models\Analytics\AnalyticsDelivery;
use App\Models\Analytics\AnalyticsKpi;
use App\Models\Analytics\AnalyticsReport;
use App\Models\Analytics\AnalyticsSchedule;
use App\Models\AuditLog;
use App\Models\Encounter;
use App\Models\Organization;
use App\Models\Patient;
use App\Services\Analytics\AnalyticsInstaller;
use App\Services\Analytics\BenchmarkService;
use App\Services\Analytics\Insight\ForecastService;
use App\Services\Analytics\Insight\NationalPack;
use App\Services\Analytics\Insight\QuestionService;
use App\Services\Analytics\Reports\ReportService;
use App\Services\Analytics\Reports\ScheduleService;
use App\Services\Analytics\Research\ResearchDatasetService;
use App\Services\Analytics\Semantic\ScopeContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

class InsightTest extends AnalyticsTestCase
{
    private function scope(): ScopeContext
    {
        return ScopeContext::system($this->organization->id);
    }

    private function visit(string $city, string $date = '2026-09-08', string $type = 'opd'): void
    {
        $p = $this->patient(['city' => $city, 'sex' => 'F']);
        Encounter::create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $p->id, 'encounter_no' => 'E-'.uniqid(), 'encounter_type' => $type, 'provider_id' => $this->provider()->id, 'encounter_date' => $date, 'status' => 'completed',
            'source' => 'walk_in', 'created_by' => $this->superAdmin()->id]);
    }

    // ---- reports and schedules ----------------------------------------------------------------------------------------------------------------

    public function test_a_saved_report_runs_exports_a_safe_csv_and_is_checked_against_the_model(): void
    {
        $this->bill(null, [['CONSULT', '1000.00']]);
        $this->runLoads();
        $svc = app(ReportService::class);
        $by = $this->superAdmin();
        $report = $svc->save($this->organization->id, $by, 'Revenue by service', null, ['fact' => 'revenue', 'measures' => ['net_revenue'], 'group_by' => ['category']], true);
        $result = $svc->run($report, $this->scope(), '2026-09-01', '2026-09-30');
        $this->assertSame(1000.0, $result['rows'][0]['net_revenue']);
        $csv = $svc->toCsv($result);
        $this->assertStringContainsString('"Kind of service","Net revenue"', $csv);
        $this->assertStringContainsString('CONSULT,1000', $csv);
        $evil = ['columns' => [['key' => 'a', 'label' => 'A']], 'rows' => [['a' => '=HYPERLINK("http://evil")'], ['a' => '@cmd']]];
        $this->assertStringContainsString("'=HYPERLINK", $svc->toCsv($evil));
        $this->assertStringContainsString("'@cmd", $svc->toCsv($evil));
        $this->expectException(ValidationException::class);
        $svc->save($this->organization->id, $by, 'Bad', null, ['fact' => 'revenue', 'measures' => ['salary']], false);
    }

    public function test_a_schedule_goes_only_to_staff_runs_with_the_owners_rights_and_is_logged(): void
    {
        Mail::fake();
        $this->app->bind(PdfRenderer::class, fn () => new class implements PdfRenderer
        {
            public function render(string $html, string $paper): string
            {
                return '%PDF-test '.strlen($html);
            }
        });
        $this->bill(null, [['CONSULT', '1000.00']]);
        $this->runLoads();
        $owner = $this->actor(['analytics.report.view', 'analytics.query.run', 'analytics.schedule.manage', 'analytics.scope.organization']);
        $reader = $this->actor(['analytics.report.view']);
        $report = app(ReportService::class)->save($this->organization->id, $owner, 'Daily revenue', null, ['fact' => 'revenue', 'measures' => ['net_revenue']], true);
        $svc = app(ScheduleService::class);
        foreach ([[['stranger@example.com']], [[]], [['not-an-address']]] as [$bad]) {
            try {
                $svc->create($report, $owner, 'daily', null, '07:00', 'csv', $bad);
                $this->fail('accepted '.json_encode($bad));
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
        $s = $svc->create($report, $owner, 'daily', null, '07:00', 'csv', [$reader->email, strtoupper($owner->email)]);
        $this->assertCount(2, $s->recipients);
        $this->assertTrue($s->next_run_at->gt(now()));
        $this->assertSame(['sent' => 0, 'failed' => 0], $svc->runDue(), 'not due yet');
        $this->assertSame(['sent' => 1, 'failed' => 0], $svc->runDue(CarbonImmutable::now()->addDays(2)));
        Mail::assertSentCount(2);
        $d = AnalyticsDelivery::query()->first();
        $this->assertSame('sent', $d->status);
        $this->assertSame(64, strlen($d->checksum));
        $this->assertTrue($s->refresh()->next_run_at->gt(now()->addDay()), 'the next run was set');
        $this->assertNotNull(AuditLog::query()->where('action', 'ANALYTICS_REPORT_SCHEDULED_SEND')->first(), 'sending is audited');

        $pdf = $svc->create($report, $owner, 'weekly', 1, '08:00', 'pdf', [$owner->email]);
        $this->assertTrue($svc->deliver($pdf));

        // the owner loses the right: the schedule fails and sends nothing
        Mail::fake();
        $owner->syncPermissions([]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->assertFalse($svc->deliver($s->refresh()));
        Mail::assertNothingSent();
        $this->assertSame('failed', AnalyticsDelivery::query()->latest('id')->first()->status);
    }

    public function test_schedule_times_follow_the_hospitals_clock_and_periods_are_whole_days(): void
    {
        $svc = app(ScheduleService::class);
        $s = new AnalyticsSchedule(['frequency' => 'daily', 'at_time' => '07:00']);
        $n = $svc->next($s, CarbonImmutable::parse('2026-09-10 00:00:00', 'UTC')); // 06:00 in Dhaka: 07:00 is still ahead today
        $this->assertSame('2026-09-10 01:00:00', $n->toDateTimeString());
        $this->assertSame(['2026-09-09', '2026-09-09'], $svc->period('daily', CarbonImmutable::parse('2026-09-10 02:00:00', 'UTC')));
        $this->assertSame(['2026-08-01', '2026-08-31'], $svc->period('monthly', CarbonImmutable::parse('2026-09-10 02:00:00', 'UTC')));
        $monthly = new AnalyticsSchedule(['frequency' => 'monthly', 'at_time' => '07:00', 'day' => 5]);
        $this->assertSame('2026-10-05 01:00:00', $svc->next($monthly, CarbonImmutable::parse('2026-09-10 00:00:00', 'UTC'))->toDateTimeString());
    }

    // ---- research datasets ----------------------------------------------------------------------------------------------------------------------

    public function test_a_research_dataset_is_deidentified_k_anonymous_and_needs_a_second_person(): void
    {
        Storage::fake('local');
        foreach (range(1, 6) as $i) {
            $this->visit('Dhaka');
        }
        foreach (range(1, 2) as $i) {
            $this->visit('Khulna');
        }
        $this->runLoads();
        $svc = app(ResearchDatasetService::class);
        $maker = $this->superAdmin();
        $checker = $this->superAdmin();
        $def = ['source' => 'encounters', 'columns' => ['month', 'visit_type', 'age_band', 'sex', 'district', 'duration_minutes'], 'from' => '2026-09-01', 'to' => '2026-09-30'];
        foreach ([['source' => 'nope'], ['columns' => ['patient_id']], ['columns' => []]] as $bad) {
            try {
                $svc->request($this->organization->id, $maker, 'x', 'A reason that is long enough to read.', $bad + $def);
                $this->fail('accepted '.json_encode($bad));
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
        try {
            $svc->request($this->organization->id, $maker, 'x', 'too short', $def);
            $this->fail('a purpose is required');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
        $ds = $svc->request($this->organization->id, $maker, 'Visits by district', 'Study of outpatient attendance by district for a thesis.', $def);
        try {
            $svc->generate($ds, $maker);
            $this->fail('generated before approval');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
        try {
            $svc->decide($ds, true, $maker);
            $this->fail('self approval');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('approver', $e->errors());
        }
        $svc->decide($ds, true, $checker);
        $made = $svc->generate($ds->refresh(), $checker);
        $this->assertSame(6, $made->row_count, 'the group of two is left out');
        $this->assertSame(2, $made->suppressed_rows);
        $csv = $svc->contents($made, $checker);
        $lines = array_map('str_getcsv', array_filter(explode("\n", $csv)));
        $this->assertSame(['patient_ref', 'month', 'visit_type', 'age_band', 'sex', 'district', 'duration_minutes'], $lines[0]);
        $this->assertCount(7, $lines);
        $this->assertStringContainsString('2026-09', $csv);
        $this->assertStringNotContainsString('2026-09-08', $csv, 'dates are reduced to the month');
        $this->assertStringNotContainsString('Khulna', $csv);
        $refs = array_column(array_slice($lines, 1), 0);
        $this->assertCount(6, array_unique($refs));
        $this->assertSame(16, strlen($refs[0]));
        foreach (Patient::query()->get() as $p) {
            $this->assertStringNotContainsString($p->first_name, $csv);
            $this->assertStringNotContainsString((string) $p->phone, $csv);
        }
        // a second dataset gives different references for the same people: datasets cannot be joined
        $ds2 = $svc->request($this->organization->id, $maker, 'Again', 'A second look at the same visits for another study.', $def);
        $svc->decide($ds2, true, $checker);
        $csv2 = $svc->contents($svc->generate($ds2->refresh(), $checker), $checker);
        $this->assertEmpty(array_intersect($refs, array_column(array_slice(array_map('str_getcsv', array_filter(explode("\n", $csv2))), 1), 0)));
        $this->assertNotNull(AuditLog::query()->where('action', 'ANALYTICS_DATASET_DOWNLOADED')->first());
        $this->assertSame('generated', AnalyticsDataset::query()->find($made->id)->status);
    }

    // ---- benchmarking ---------------------------------------------------------------------------------------------------------------------------

    public function test_benchmarks_need_opt_in_and_enough_peers_and_never_name_them(): void
    {
        app(AnalyticsInstaller::class)->install();
        $kpi = AnalyticsKpi::query()->where('code', 'opd_visits')->first();
        $svc = app(BenchmarkService::class);
        $by = $this->superAdmin();
        $none = $svc->compare($kpi, $this->organization->id, '2026-09-01', '2026-09-30');
        $this->assertFalse($none['available']);
        $this->assertStringContainsString('not agreed', $none['reason']);
        $svc->optIn($this->organization->id, true, $by);
        $this->assertStringContainsString('Not enough', $svc->compare($kpi, $this->organization->id, '2026-09-01', '2026-09-30')['reason']);
        $peers = [];
        foreach ([2, 4, 6, 10] as $n) {
            $o = Organization::factory()->create();
            $svc->optIn($o->id, true, $by);
            foreach (range(1, $n) as $i) {
                $this->wh('fact_encounters')->insert(['encounter_id' => $o->id * 1000 + $i, 'organization_id' => $o->id, 'date_key' => 20260908, 'encounter_type' => 'opd', 'status' => 'completed', 'completed' => true]);
            }
            $peers[] = $o;
        }
        foreach (range(1, 5) as $i) {
            $this->visit('Dhaka');
        }
        $this->runLoads();
        $r = $svc->compare($kpi, $this->organization->id, '2026-09-01', '2026-09-30');
        $this->assertTrue($r['available']);
        $this->assertSame(4, $r['peers']);
        $this->assertSame(5.0, $r['median']);
        $this->assertSame(5.0, $r['own']);
        $this->assertStringNotContainsString($peers[0]->name, json_encode($r));
        $this->assertArrayNotHasKey('organizations', $r);
        $svc->optIn($peers[0]->id, false, $by);
        $this->assertSame(3, $svc->compare($kpi, $this->organization->id, '2026-09-01', '2026-09-30')['peers'], 'a hospital that leaves is no longer compared');
        $own = AnalyticsKpi::create(['organization_id' => $this->organization->id, 'code' => 'mine', 'name' => 'Mine', 'domain' => 'operations', 'unit' => 'count', 'good_direction' => 'up', 'definition' => ['fact' => 'encounters', 'measure' => 'visits', 'filters' => []], 'version' => 1, 'status' => 'active']);
        $this->assertFalse($svc->compare($own, $this->organization->id, '2026-09-01', '2026-09-30')['available']);
    }

    // ---- national pack ------------------------------------------------------------------------------------------------------------------------

    public function test_the_national_pack_computes_figures_and_exports_only_what_is_mapped(): void
    {
        foreach (range(1, 4) as $i) {
            $this->visit('Dhaka');
        }
        $this->visit('Dhaka', '2026-09-09', 'ipd');
        $this->runLoads();
        $pack = app(NationalPack::class);
        $v = $pack->compute($this->organization->id, '2026-09');
        $this->assertSame(4, $v['opd_attendance']);
        $this->assertNull($v['ipd_bed_occupancy_pct']);
        $this->assertSame(array_keys(NationalPack::INDICATORS), array_keys($v));
        $unmapped = $pack->dhis2($this->organization->id, '2026-09');
        $this->assertFalse($unmapped['ready']);
        $this->assertContains('org_unit', $unmapped['unmapped']);
        $this->assertContains('opd_attendance', $unmapped['unmapped']);
        $this->assertSame([], $unmapped['payload']['dataValues']);
        config(['analytics.national.data_set' => 'DS1', 'analytics.national.org_unit' => 'OU1', 'analytics.national.elements' => array_fill_keys(array_keys(NationalPack::INDICATORS), 'DE')]);
        config(['analytics.national.elements.opd_attendance' => 'DE_OPD']);
        $ready = $pack->dhis2($this->organization->id, '2026-09');
        $this->assertTrue($ready['ready']);
        $this->assertSame('202609', $ready['payload']['period']);
        $this->assertSame(['dataElement' => 'DE_OPD', 'value' => '4'], $ready['payload']['dataValues'][0]);
    }

    // ---- forecast and questions -------------------------------------------------------------------------------------------------------------------

    public function test_the_forecast_follows_the_weekly_pattern_and_reports_its_own_error(): void
    {
        $svc = app(ForecastService::class);
        $history = [];
        for ($d = CarbonImmutable::parse('2026-05-01'); $d->lte(CarbonImmutable::parse('2026-08-31')); $d = $d->addDay()) {
            $history[$d->toDateString()] = $d->isoWeekday() === 5 ? 10.0 : 100.0; // Fridays are quiet
        }
        $pred = $svc->predict($history, 7);
        $this->assertCount(7, $pred);
        foreach ($pred as $date => $value) {
            $expected = CarbonImmutable::parse($date)->isoWeekday() === 5 ? 10.0 : 100.0;
            $this->assertEqualsWithDelta($expected, $value, 12.0, $date);
        }
        $bt = $svc->backtest($history, 14);
        $this->assertLessThan(15, $bt['error_pct']);
        $this->assertNull($svc->backtest(array_slice($history, 0, 20, true), 14)['error_pct'], 'too little history to judge');
        foreach (range(1, 3) as $i) {
            $this->visit('Dhaka', now()->subDay()->toDateString());
        }
        $this->runLoads();
        $f = $svc->run($this->organization->id, 'opd_visits');
        $this->assertSame(14, $f->horizon_days);
        $this->assertTrue($f->points['advisory']);
        $this->assertCount(14, $f->points['forecast']);
        $this->expectException(\InvalidArgumentException::class);
        $svc->series($this->organization->id, 'salaries', 10);
    }

    public function test_questions_become_queries_that_can_only_read(): void
    {
        $this->bill(null, [['CONSULT', '1000.00'], ['LAB', '500.00']]);
        $this->runLoads();
        $svc = app(QuestionService::class);
        $a = $svc->ask('What was the net revenue by category this month?', $this->scope());
        $this->assertTrue($a['understood']);
        $this->assertSame(['revenue', ['net_revenue'], ['category']], [$a['query']['fact'], $a['query']['measures'], $a['query']['group_by']]);
        $this->assertSame(1000.0, $a['result']['rows'][0]['net_revenue']);
        $this->assertStringContainsString('select', $a['result']['meta']['sql']);
        $this->assertStringContainsString('check the query', $a['message']);
        $day = $svc->ask('billed per day last 7 days', $this->scope());
        $this->assertSame('day', $day['query']['grain']);
        $unknown = $svc->ask('Tell me a joke about cats', $this->scope());
        $this->assertFalse($unknown['understood']);
        $this->assertNotEmpty($unknown['suggestions']);
        $attack = $svc->ask('revenue by category; drop table users; --', $this->scope());
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue($attack['understood']);
    }

    public function test_reports_table_has_no_leak_between_hospitals(): void
    {
        $owner = $this->superAdmin();
        $other = Organization::factory()->create();
        $svc = app(ReportService::class);
        $svc->save($this->organization->id, $owner, 'Mine shared', null, ['fact' => 'revenue', 'measures' => ['net_revenue']], true);
        $svc->save($this->organization->id, $owner, 'Mine private', null, ['fact' => 'revenue', 'measures' => ['net_revenue']], false);
        $svc->save($other->id, $owner, 'Theirs', null, ['fact' => 'revenue', 'measures' => ['net_revenue']], true);
        $someoneElse = $this->actor(['analytics.report.view']);
        $this->assertSame(['Mine shared'], $svc->visibleTo($someoneElse, $this->organization->id)->pluck('name')->all());
        $this->assertSame(['Mine private', 'Mine shared'], $svc->visibleTo($owner, $this->organization->id)->pluck('name')->sort()->values()->all());
        $this->assertSame(3, AnalyticsReport::query()->count());
    }
}
