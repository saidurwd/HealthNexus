<?php

namespace Modules\Analytics\Http\Controllers\Web;

use App\Models\Analytics\AnalyticsDashboard;
use App\Models\Analytics\AnalyticsKpi;
use App\Models\Analytics\AnalyticsKpiVersion;
use App\Services\Analytics\Kpi\DashboardService;
use App\Services\Analytics\Kpi\KpiService;
use App\Services\Analytics\Reports\ReportService;
use App\Services\Analytics\Semantic\SemanticCatalog;
use App\Services\Analytics\Semantic\SemanticQuery;
use App\Services\AuditLogger;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Dashboards, KPIs and the explorer (any question over the semantic model, with drill-down). */
class AnalyticsDashboardController extends AnalyticsController
{
    public function __construct(TenantContextResolver $tenant, private readonly DashboardService $dashboards, private readonly KpiService $kpis, private readonly SemanticQuery $queries, private readonly ReportService $reports)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'analytics.dashboard.view');
        $list = $this->dashboards->visibleTo($request->user(), $this->organizationId());

        return view('admin.analytics.index', ['dashboards' => $list]);
    }

    public function show(Request $request, string $dashboard)
    {
        $this->allow($request, 'analytics.dashboard.view');
        $d = AnalyticsDashboard::query()->where('public_id', $dashboard)->where(fn ($q) => $q->whereNull('organization_id')->orWhere('organization_id', $this->organizationId()))->firstOrFail();
        abort_unless($d->organization_id === null || $d->shared || $d->created_by === $request->user()->id, 404);
        [$from, $to] = $this->range($request);

        return view('admin.analytics.dashboard', ['d' => $d, 'widgets' => $this->dashboards->render($d, $this->scope($request), $from, $to), 'from' => $from, 'to' => $to]);
    }

    public function create(Request $request)
    {
        $this->allow($request, 'analytics.dashboard.manage');

        return view('admin.analytics.dashboard-form', ['kpis' => $this->kpis->forOrganization($this->organizationId()), 'catalogue' => SemanticCatalog::facts()]);
    }

    public function store(Request $request)
    {
        $this->allow($request, 'analytics.dashboard.manage');
        $d = $request->validate(['name' => ['required', 'string', 'max:120'], 'shared' => ['nullable', 'boolean'], 'widgets' => ['required', 'array', 'min:1', 'max:20'], 'widgets.*.type' => ['nullable', 'in:kpi,chart,table'], 'widgets.*.kpi' => ['nullable', 'string', 'max:60'], 'widgets.*.title' => ['nullable', 'string', 'max:100'],
            'widgets.*.fact' => ['nullable', 'string'], 'widgets.*.measure' => ['nullable', 'string'], 'widgets.*.group_by' => ['nullable', 'string'], 'widgets.*.grain' => ['nullable', 'in:day,month,year'], 'widgets.*.kind' => ['nullable', 'in:bar,line']]);
        $widgets = [];
        foreach ($d['widgets'] as $w) {
            if (empty($w['type'])) {
                continue;
            }
            $widgets[] = $w['type'] === 'kpi' ? ['type' => 'kpi', 'kpi' => $w['kpi'] ?? ''] : ['type' => $w['type'], 'title' => $w['title'] ?? '', 'kind' => $w['kind'] ?? 'bar',
                'query' => array_filter(['fact' => $w['fact'] ?? '', 'measures' => [$w['measure'] ?? ''], 'group_by' => ($w['group_by'] ?? '') !== '' ? [$w['group_by']] : [], 'grain' => ($w['grain'] ?? '') ?: null], fn ($v) => $v !== null)];
        }
        $saved = $this->dashboards->save($this->organizationId(), $d['name'], $widgets, $request->user(), $request->boolean('shared'));

        return redirect()->route('admin.analytics.dashboards.show', $saved->public_id)->with('success', __('Dashboard saved.'));
    }

    public function destroy(Request $request, string $dashboard)
    {
        $this->allow($request, 'analytics.dashboard.manage');
        $d = $this->find(AnalyticsDashboard::class, $dashboard);
        abort_if($d->is_system || ($d->created_by !== $request->user()->id && ! $request->user()->can('analytics.dashboard.manage')), 403);
        $d->delete();

        return redirect()->route('admin.analytics.index')->with('success', __('Dashboard deleted.'));
    }

    // ---- KPIs ---------------------------------------------------------------------------------------------------------------------------------

    public function kpis(Request $request)
    {
        $this->allow($request, 'analytics.kpi.view');
        [$from, $to] = $this->range($request);
        $scope = $this->scope($request);
        $rows = $this->kpis->forOrganization($this->organizationId())->map(fn (AnalyticsKpi $k) => ['kpi' => $k, 'r' => $this->kpis->evaluate($k, $scope, $from, $to)]);

        return view('admin.analytics.kpis', ['rows' => $rows, 'from' => $from, 'to' => $to]);
    }

    public function kpi(Request $request, string $kpi)
    {
        $this->allow($request, 'analytics.kpi.view');
        $k = $this->find(AnalyticsKpi::class, $kpi, true);
        [$from, $to] = $this->range($request);
        $def = $k->definition;
        $trend = $this->queries->run(['fact' => $def['fact'], 'measures' => [$def['measure']], 'filters' => $def['filters'] ?? [], 'grain' => 'month', 'from' => now()->subMonths(11)->startOfMonth()->toDateString(), 'to' => now()->toDateString()], $this->scope($request));

        return view('admin.analytics.kpi', ['k' => $k, 'r' => $this->kpis->evaluate($k, $this->scope($request), $from, $to), 'trend' => $trend, 'measure' => $def['measure'], 'versions' => AnalyticsKpiVersion::query()->where('kpi_id', $k->id)->orderByDesc('version')->get(),
            'target' => $this->kpis->targetFor($k, $this->organizationId()), 'from' => $from, 'to' => $to, 'catalogue' => SemanticCatalog::facts()]);
    }

    public function setTarget(Request $request, string $kpi)
    {
        $this->allow($request, 'analytics.kpi.manage');
        $k = $this->find(AnalyticsKpi::class, $kpi, true);
        $d = $request->validate(['target' => ['nullable', 'numeric']]);
        $this->kpis->setTarget($k, $this->organizationId(), isset($d['target']) ? (string) $d['target'] : null, $request->user());

        return back()->with('success', __('Target saved.'));
    }

    public function saveKpi(Request $request)
    {
        $this->allow($request, 'analytics.kpi.manage');
        $d = $request->validate(['id' => ['nullable', 'string'], 'name' => ['required', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:400'], 'domain' => ['required', 'in:operations,clinical,diagnostics,pharmacy,finance,people,quality'], 'unit' => ['required', 'string'], 'good_direction' => ['required', 'in:up,down,none'],
            'fact' => ['required', 'string'], 'measure' => ['required', 'string'], 'filter_dimension' => ['nullable', 'string'], 'filter_value' => ['nullable', 'string', 'max:100'], 'target' => ['nullable', 'numeric'], 'note' => ['nullable', 'string', 'max:300']]);
        $filters = ($d['filter_dimension'] ?? '') !== '' && ($d['filter_value'] ?? '') !== '' ? [['dimension' => $d['filter_dimension'], 'op' => '=', 'value' => $d['filter_value']]] : [];
        $existing = ! empty($d['id']) ? $this->find(AnalyticsKpi::class, $d['id']) : null;
        $k = $this->kpis->save($this->organizationId(), $d + ['filters' => $filters, 'target' => isset($d['target']) ? (string) $d['target'] : null], $request->user(), $existing);

        return redirect()->route('admin.analytics.kpis.show', $k->public_id)->with('success', __('KPI saved.'));
    }

    public function retireKpi(Request $request, string $kpi)
    {
        $this->allow($request, 'analytics.kpi.manage');
        $this->kpis->retire($this->find(AnalyticsKpi::class, $kpi), $this->organizationId(), $request->user());

        return redirect()->route('admin.analytics.kpis')->with('success', __('KPI retired.'));
    }

    // ---- explorer ---------------------------------------------------------------------------------------------------------------------------

    /** @return array<string,mixed> */
    private function queryFrom(Request $request): array
    {
        $fact = (string) $request->query('fact', 'revenue');
        $f = SemanticCatalog::fact($fact) ?? SemanticCatalog::fact('revenue');
        $fact = SemanticCatalog::fact($fact) ? $fact : 'revenue';
        $measures = array_values(array_filter((array) $request->query('measures', [array_key_first($f['measures'])])));
        $filters = [];
        foreach ((array) $request->query('fd', []) as $i => $dim) {
            $v = ((array) $request->query('fv', []))[$i] ?? '';
            if ($dim !== '' && $v !== '') {
                $filters[] = ['dimension' => $dim, 'op' => ((array) $request->query('fo', []))[$i] ?? '=', 'value' => in_array(((array) $request->query('fo', []))[$i] ?? '=', ['in', 'not_in'], true) ? array_map('trim', explode(',', $v)) : $v];
            }
        }
        [$from, $to] = $this->range($request);

        return ['fact' => $fact, 'measures' => $measures, 'group_by' => array_values(array_filter((array) $request->query('group_by', []))), 'grain' => $request->query('grain') ?: null, 'filters' => $filters, 'from' => $from, 'to' => $to,
            'order_by' => $request->query('order_by') ?: null, 'direction' => $request->query('direction', 'desc'), 'limit' => (int) $request->query('limit', 200)];
    }

    public function explore(Request $request)
    {
        $this->allow($request, 'analytics.query.run');
        $q = $this->queryFrom($request);
        $result = null;
        $error = null;
        try {
            $result = $this->queries->run($q, $this->scope($request));
        } catch (ValidationException $e) {
            $error = implode(' ', array_merge(...array_values($e->errors())));
        }
        if ($result && $request->query('export') === 'csv') {
            $this->allow($request, 'analytics.report.export');
            app(AuditLogger::class)->log('ANALYTICS_EXPLORE_EXPORT', null, null, null, ['fact' => $q['fact'], 'measures' => $q['measures'], 'rows' => count($result['rows'])]);

            return response($this->reports->toCsv($result), 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="analytics-'.now()->format('Ymd-His').'.csv"']);
        }

        return view('admin.analytics.explore', ['q' => $q, 'result' => $result, 'error' => $error, 'catalogue' => SemanticCatalog::facts(), 'from' => $q['from'], 'to' => $q['to']]);
    }
}
