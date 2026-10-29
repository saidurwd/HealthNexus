<?php

namespace App\Services\Analytics\Kpi;

use App\Models\Analytics\AnalyticsDashboard;
use App\Models\Analytics\AnalyticsKpi;
use App\Models\User;
use App\Services\Analytics\Semantic\ScopeContext;
use App\Services\Analytics\Semantic\SemanticQuery;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Throwable;

/** Dashboards: a list of widgets (KPI tile, chart, table). Rendering evaluates each widget with the viewer's own scope; a widget that fails shows its message and does not break the page. */
class DashboardService
{
    public function __construct(private readonly KpiService $kpis, private readonly SemanticQuery $queries) {}

    public function installSystem(): int
    {
        $n = 0;
        foreach (DashboardCatalog::system() as $d) {
            $row = AnalyticsDashboard::query()->whereNull('organization_id')->where('code', $d['code'])->first();
            if ($row === null) {
                AnalyticsDashboard::create(['organization_id' => null, 'code' => $d['code'], 'name' => $d['name'], 'role_key' => $d['role_key'], 'is_system' => true, 'shared' => true, 'widgets' => $d['widgets']]);
                $n++;
            }
        }

        return $n;
    }

    /** What a user may open: the standard ones, the hospital's shared ones and their own. @return Collection<int,AnalyticsDashboard> */
    public function visibleTo(User $user, int $organizationId): Collection
    {
        return AnalyticsDashboard::query()->where(fn ($q) => $q->whereNull('organization_id')->orWhere(fn ($w) => $w->where('organization_id', $organizationId)->where(fn ($x) => $x->where('shared', true)->orWhere('created_by', $user->id))))->orderByDesc('is_system')->orderBy('name')->get();
    }

    public function save(int $organizationId, string $name, array $widgets, User $by, bool $shared = false, ?AnalyticsDashboard $existing = null): AnalyticsDashboard
    {
        $name = trim($name);
        if ($name === '' || $widgets === []) {
            throw ValidationException::withMessages(['name' => 'Give the dashboard a name and at least one widget.']);
        }
        foreach ($widgets as $w) {
            $this->validateWidget($w);
        }
        if ($existing && ($existing->is_system || $existing->organization_id !== $organizationId || ($existing->created_by !== $by->id && ! $by->can('analytics.dashboard.manage')))) {
            throw ValidationException::withMessages(['dashboard' => 'This dashboard cannot be changed by you.']);
        }
        $data = ['name' => mb_substr($name, 0, 120), 'widgets' => array_values($widgets), 'shared' => $shared];
        if ($existing) {
            $existing->update($data);

            return $existing;
        }
        $code = 'd'.substr(md5($name.microtime()), 0, 10);

        return AnalyticsDashboard::create($data + ['organization_id' => $organizationId, 'code' => $code, 'role_key' => 'custom', 'is_system' => false, 'created_by' => $by->id]);
    }

    /** @param array<string,mixed> $w */
    public function validateWidget(array $w): void
    {
        $type = $w['type'] ?? null;
        if (! in_array($type, ['kpi', 'chart', 'table'], true)) {
            throw ValidationException::withMessages(['widgets' => 'A widget is a KPI, a chart or a table.']);
        }
        if ($type === 'kpi') {
            if (! isset($w['kpi']) || ! is_string($w['kpi'])) {
                throw ValidationException::withMessages(['widgets' => 'A KPI widget needs a KPI.']);
            }

            return;
        }
        $this->queries->normalise((array) ($w['query'] ?? []));
    }

    /**
     * @return list<array<string,mixed>> the widgets with their values
     */
    public function render(AnalyticsDashboard $dashboard, ScopeContext $scope, ?string $from = null, ?string $to = null): array
    {
        $out = [];
        foreach ($dashboard->widgets as $w) {
            $item = ['type' => $w['type'], 'title' => $w['title'] ?? null, 'kind' => $w['kind'] ?? null, 'query' => $w['query'] ?? null];
            try {
                if ($w['type'] === 'kpi') {
                    $kpi = AnalyticsKpi::query()->where('status', 'active')->where('code', $w['kpi'])->where(fn ($q) => $q->where('organization_id', $scope->organizationId)->orWhereNull('organization_id'))->orderByDesc('organization_id')->first();
                    if ($kpi === null) {
                        throw new \RuntimeException('KPI not found.');
                    }
                    $item += ['kpi' => $kpi, 'result' => $this->kpis->evaluate($kpi, $scope, $from, $to)];
                } else {
                    $q = $w['query'] + array_filter(['from' => $from, 'to' => $to]);
                    $item += ['data' => $this->queries->run($q, $scope), 'run_query' => $q];
                }
            } catch (ValidationException $e) {
                $item['error'] = implode(' ', array_merge(...array_values($e->errors())));
            } catch (Throwable $e) {
                $item['error'] = $e->getMessage();
            }
            $out[] = $item;
        }

        return $out;
    }
}
