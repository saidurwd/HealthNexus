<?php

namespace App\Services\Demo\History\Modules;

use App\Models\Analytics\AnalyticsDataset;
use App\Models\Analytics\AnalyticsKpi;
use App\Services\Analytics\AnalyticsInstaller;
use App\Services\Analytics\BenchmarkService;
use App\Services\Analytics\DataQualityService;
use App\Services\Analytics\Insight\ForecastService;
use App\Services\Analytics\Kpi\DashboardService;
use App\Services\Analytics\Kpi\KpiService;
use App\Services\Analytics\LoadRunner;
use App\Services\Analytics\Reports\ReportService;
use App\Services\Analytics\Reports\ScheduleService;
use App\Services\Analytics\Research\ResearchDatasetService;
use App\Services\Demo\History\DemoHistoryModule;
use App\Services\Demo\History\HistoryContext;
use Throwable;

/**
 * The warehouse and what sits on it. Everything the other modules did in the six months is loaded (the first load reads it all, in chunks), the standard KPIs and five role dashboards are installed,
 * the hospital sets a few targets of its own, makes a KPI and a dashboard of its own, saves three reports and schedules two, runs the data-quality checks, asks for a research dataset that a second
 * person approves, takes part in the anonymous comparison, and makes the planning forecasts.
 */
class AnalyticsHistory implements DemoHistoryModule
{
    public function __construct(
        private readonly AnalyticsInstaller $installer,
        private readonly LoadRunner $runner,
        private readonly KpiService $kpis,
        private readonly DashboardService $dashboards,
        private readonly ReportService $reports,
        private readonly ScheduleService $schedules,
        private readonly DataQualityService $quality,
        private readonly ResearchDatasetService $research,
        private readonly BenchmarkService $benchmark,
        private readonly ForecastService $forecasts,
    ) {}

    public function name(): string
    {
        return 'BI and analytics';
    }

    public function module(): string
    {
        return 'analytics';
    }

    public function run(HistoryContext $context): string
    {
        $org = $context->organization->id;
        $analyst = $context->staff('data_analyst')->first();
        $manager = $context->staff('analytics_manager')->first();

        return $context->at($context->to->setTime(19, 0), function () use ($context, $org, $analyst, $manager) {
            $installed = $this->installer->install();
            $runs = $this->runner->run(null, $manager);
            $failed = collect($runs)->where('status', 'failed')->keys()->all();
            $rows = (int) collect($runs)->sum('rows_written');

            // targets the hospital sets for itself (the standard ones are examples)
            foreach (['avg_wait' => '20', 'ed_door_to_doctor' => '25', 'collection_rate' => '85', 'bed_occupancy' => '75'] as $code => $target) {
                $k = AnalyticsKpi::query()->whereNull('organization_id')->where('code', $code)->first();
                $k && $this->kpis->setTarget($k, $org, $target, $manager);
            }
            $own = $this->kpis->save($org, ['name' => 'Laboratory revenue', 'description' => 'Net revenue from laboratory services.', 'domain' => 'finance', 'unit' => 'money', 'good_direction' => 'up', 'fact' => 'revenue', 'measure' => 'net_revenue', 'filters' => [['dimension' => 'category', 'op' => '=', 'value' => 'LAB']]], $analyst);
            $this->kpis->save($org, ['name' => 'Laboratory revenue', 'description' => 'Net revenue from laboratory services (gross, before discounts).', 'domain' => 'finance', 'unit' => 'money', 'good_direction' => 'up', 'fact' => 'revenue', 'measure' => 'gross_revenue', 'filters' => [['dimension' => 'category', 'op' => '=', 'value' => 'LAB']], 'note' => 'Finance asked for the figure before discounts'], $analyst, $own);

            $this->dashboards->save($org, 'Morning round', [
                ['type' => 'kpi', 'kpi' => 'bed_occupancy'], ['type' => 'kpi', 'kpi' => 'ed_door_to_doctor'], ['type' => 'kpi', 'kpi' => 'opd_visits'], ['type' => 'kpi', 'kpi' => $own->code],
                ['type' => 'chart', 'title' => 'Outpatient visits by day', 'kind' => 'line', 'query' => ['fact' => 'encounters', 'measures' => ['opd_visits'], 'grain' => 'day']],
                ['type' => 'chart', 'title' => 'Emergency arrivals by hour', 'kind' => 'bar', 'query' => ['fact' => 'emergency', 'measures' => ['ed_visits'], 'group_by' => ['hour'], 'order_by' => 'hour', 'direction' => 'asc']],
            ], $analyst, true);

            $revenue = $this->reports->save($org, $analyst, 'Revenue by service', 'Net and gross revenue for each kind of service.', ['fact' => 'revenue', 'measures' => ['net_revenue', 'gross_revenue'], 'group_by' => ['category']], true);
            $visits = $this->reports->save($org, $analyst, 'Visits by doctor', 'Outpatient visits and average visit length for each doctor.', ['fact' => 'encounters', 'measures' => ['opd_visits', 'avg_visit_minutes'], 'group_by' => ['doctor'], 'limit' => 50], true);
            $this->reports->save($org, $analyst, 'Claims by insurer', 'What insurers owe and have paid.', ['fact' => 'claims', 'measures' => ['claims', 'claimed', 'claims_paid', 'claims_outstanding', 'denial_rate'], 'group_by' => ['payer']], true);
            $emails = $context->staff('analytics_manager')->pluck('email')->merge($context->staff('executive_viewer')->pluck('email'))->unique()->take(3)->values()->all();
            $daily = $this->schedules->create($revenue, $analyst, 'weekly', 1, '07:30', 'csv', $emails);
            $this->schedules->create($visits, $analyst, 'monthly', 3, '08:00', 'csv', array_slice($emails, 0, 1));
            $this->schedules->deliver($daily); // one went out this week

            $this->benchmark->optIn($org, true, $manager);
            $dq = $this->quality->run();

            // a research dataset: asked for by the analyst, approved and made by a second person
            $ds = $this->research->request($org, $analyst, 'Outpatient visits by age and district', 'A university thesis on how far patients travel to the outpatient clinic, by age group.', ['source' => 'encounters', 'columns' => ['month', 'visit_type', 'age_band', 'sex', 'district'], 'from' => $context->from->toDateString(), 'to' => $context->to->toDateString()]);
            $this->research->decide($ds, true, $manager, 'Approved for the thesis; no names are in it.');
            try {
                $this->research->generate($ds->refresh(), $manager);
            } catch (Throwable) {
                // a disk that cannot be written to leaves the dataset approved but not made
            }
            foreach (array_keys(ForecastService::METRICS) as $metric) {
                $this->forecasts->run($org, $metric);
            }
            $made = AnalyticsDataset::query()->where('organization_id', $org)->where('status', 'generated')->first();

            return sprintf('%d KPIs and %d dashboards installed, warehouse loaded (%s rows%s), 4 targets set, 1 KPI of our own in 2 versions, 1 dashboard, 3 reports (2 scheduled, 1 sent), data quality: %d failing rule(s) and %d open issue(s), research dataset %s, 5 forecasts',
                $installed['kpis'], $installed['dashboards'], number_format($rows), $failed ? '; FAILED: '.implode(', ', $failed) : '', $dq['failing'], $this->quality->openCount($org), $made ? 'made ('.$made->row_count.' rows, '.$made->suppressed_rows.' left out)' : 'approved');
        });
    }
}
