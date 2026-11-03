<?php

namespace Modules\Analytics\Http\Controllers\Web;

use App\Models\Analytics\AnalyticsDataset;
use App\Models\Analytics\AnalyticsForecast;
use App\Models\Analytics\AnalyticsKpi;
use App\Services\Analytics\BenchmarkService;
use App\Services\Analytics\Insight\ForecastService;
use App\Services\Analytics\Insight\NationalPack;
use App\Services\Analytics\Insight\QuestionService;
use App\Services\Analytics\Research\ResearchDatasetService;
use App\Services\AuditLogger;
use App\Services\SettingsService;
use App\Services\TenantContextResolver;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/** Research datasets, anonymous benchmarks, the national reporting pack, planning forecasts and "ask a question". */
class AnalyticsInsightController extends AnalyticsController
{
    public function __construct(TenantContextResolver $tenant, private readonly ResearchDatasetService $research, private readonly BenchmarkService $benchmark, private readonly NationalPack $national, private readonly ForecastService $forecasts,
        private readonly QuestionService $questions, private readonly SettingsService $settings)
    {
        parent::__construct($tenant);
    }

    // ---- research ---------------------------------------------------------------------------------------------------------------------------

    public function research(Request $request)
    {
        $this->allow($request, 'analytics.research.request');
        $rows = AnalyticsDataset::query()->where('organization_id', $this->organizationId())->latest('id')->get();

        return view('admin.analytics.research', ['rows' => $rows, 'sources' => ResearchDatasetService::SOURCES, 'k' => (int) config('analytics.research.k_anonymity', 5)]);
    }

    public function requestDataset(Request $request)
    {
        $this->allow($request, 'analytics.research.request');
        $d = $request->validate(['name' => ['required', 'string', 'max:150'], 'purpose' => ['required', 'string', 'max:400'], 'source' => ['required', 'string'], 'columns' => ['required', 'array', 'min:1'], 'columns.*' => ['string'], 'from' => ['required', 'date'], 'to' => ['required', 'date']]);
        $this->research->request($this->organizationId(), $request->user(), $d['name'], $d['purpose'], ['source' => $d['source'], 'columns' => $d['columns'], 'from' => $d['from'], 'to' => $d['to']]);

        return back()->with('success', __('Requested. A second person must approve it before the file is made.'));
    }

    public function decideDataset(Request $request, string $dataset)
    {
        $this->allow($request, 'analytics.research.approve');
        $ds = $this->find(AnalyticsDataset::class, $dataset);
        $d = $request->validate(['decision' => ['required', 'in:approve,reject'], 'note' => ['nullable', 'string', 'max:300']]);
        $this->research->decide($ds, $d['decision'] === 'approve', $request->user(), $d['note'] ?? null);
        if ($d['decision'] === 'approve') {
            $this->research->generate($ds->refresh(), $request->user());
        }

        return back()->with('success', $d['decision'] === 'approve' ? __('Approved and made.') : __('Declined.'));
    }

    public function downloadDataset(Request $request, string $dataset)
    {
        $this->allow($request, 'analytics.research.download');
        $ds = $this->find(AnalyticsDataset::class, $dataset);

        return response($this->research->contents($ds, $request->user()), 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="dataset-'.$ds->public_id.'.csv"']);
    }

    // ---- benchmarks ---------------------------------------------------------------------------------------------------------------------------

    public function benchmark(Request $request)
    {
        $this->allow($request, 'analytics.benchmark.view');
        [$from, $to] = $this->range($request);
        $org = $this->organizationId();
        $in = $this->benchmark->isIn($org);
        $rows = $in ? AnalyticsKpi::query()->whereNull('organization_id')->where('status', 'active')->orderBy('domain')->orderBy('name')->get()->map(fn ($k) => ['kpi' => $k, 'b' => $this->benchmark->compare($k, $org, $from, $to)]) : collect();

        return view('admin.analytics.benchmark', ['in' => $in, 'rows' => $rows, 'from' => $from, 'to' => $to, 'min' => (int) config('analytics.benchmark.min_peers', 3)]);
    }

    public function optIn(Request $request)
    {
        $this->allow($request, 'analytics.benchmark.manage');
        $this->benchmark->optIn($this->organizationId(), $request->boolean('in'), $request->user());

        return back()->with('success', $request->boolean('in') ? __('Your hospital now takes part in the anonymous comparison.') : __('Your hospital no longer takes part.'));
    }

    // ---- national pack ------------------------------------------------------------------------------------------------------------------------

    public function national(Request $request)
    {
        $this->allow($request, 'analytics.national.view');
        $month = $request->validate(['month' => ['nullable', 'regex:/^\d{4}-\d{2}$/']])['month'] ?? CarbonImmutable::now((string) config('analytics.timezone', 'Asia/Dhaka'))->subMonthNoOverflow()->format('Y-m');
        $values = $this->national->compute($this->organizationId(), $month);
        $dhis = $this->national->dhis2($this->organizationId(), $month);
        if ($request->query('export') === 'dhis2') {
            $this->allow($request, 'analytics.national.export');
            abort_unless($dhis['ready'], 422, 'The national codes are not set yet: '.implode(', ', $dhis['unmapped']));
            app(AuditLogger::class)->log('ANALYTICS_NATIONAL_EXPORT', null, null, null, ['month' => $month, 'values' => count($dhis['payload']['dataValues'])]);

            return response()->json($dhis['payload'], 200, ['Content-Disposition' => 'attachment; filename="dhis2-'.$month.'.json"']);
        }
        if ($request->query('export') === 'csv') {
            $this->allow($request, 'analytics.national.export');

            return response("Indicator,Value\n".collect(NationalPack::INDICATORS)->map(fn ($label, $k) => '"'.$label.'",'.($values[$k] ?? ''))->implode("\n"), 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="national-pack-'.$month.'.csv"']);
        }

        return view('admin.analytics.national', ['month' => $month, 'values' => $values, 'labels' => NationalPack::INDICATORS, 'dhis' => $dhis, 'map' => $this->national->mapping()]);
    }

    public function saveNational(Request $request)
    {
        $this->allow($request, 'analytics.national.manage');
        $d = $request->validate(['data_set' => ['nullable', 'string', 'max:60'], 'org_unit' => ['nullable', 'string', 'max:60'], 'elements' => ['nullable', 'array'], 'elements.*' => ['nullable', 'string', 'max:60']]);
        $this->settings->set('analytics.national.data_set', $d['data_set'] ?? '', 'analytics');
        $this->settings->set('analytics.national.org_unit', $d['org_unit'] ?? '', 'analytics');
        $this->settings->set('analytics.national.elements', array_filter($d['elements'] ?? [], fn ($v) => $v !== null && $v !== ''), 'analytics');

        return back()->with('success', __('Saved.'));
    }

    // ---- forecasts and questions ---------------------------------------------------------------------------------------------------------------

    public function forecast(Request $request)
    {
        $this->allow($request, 'analytics.forecast.view');
        $metric = array_key_exists($request->query('metric'), ForecastService::METRICS) ? $request->query('metric') : 'opd_visits';
        $f = AnalyticsForecast::query()->where(['organization_id' => $this->organizationId(), 'metric' => $metric])->latest('generated_at')->first();

        return view('admin.analytics.forecast', ['metric' => $metric, 'metrics' => ForecastService::METRICS, 'f' => $f]);
    }

    public function makeForecast(Request $request)
    {
        $this->allow($request, 'analytics.forecast.view');
        $metric = $request->validate(['metric' => ['required', 'in:'.implode(',', array_keys(ForecastService::METRICS))]])['metric'];
        $this->forecasts->run($this->organizationId(), $metric);

        return redirect()->route('admin.analytics.forecast', ['metric' => $metric])->with('success', __('Forecast made.'));
    }

    public function ask(Request $request)
    {
        $this->allow($request, 'analytics.question.ask');
        $q = trim((string) $request->query('q', ''));
        $answer = $q !== '' ? $this->questions->ask(mb_substr($q, 0, 300), $this->scope($request)) : null;

        return view('admin.analytics.ask', ['q' => $q, 'answer' => $answer]);
    }
}
