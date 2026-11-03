<?php

namespace App\Services\Analytics\Kpi;

use App\Models\Analytics\AnalyticsKpi;
use App\Models\Analytics\AnalyticsKpiTarget;
use App\Models\Analytics\AnalyticsKpiVersion;
use App\Models\User;
use App\Services\Analytics\Semantic\ScopeContext;
use App\Services\Analytics\Semantic\SemanticCatalog;
use App\Services\Analytics\Semantic\SemanticQuery;
use App\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/** KPI definitions (versioned) and their values. A KPI is a semantic query with one figure; the engine adds the previous period, the change and whether it is on target. */
class KpiService
{
    public function __construct(private readonly SemanticQuery $queries, private readonly AuditLogger $audit) {}

    /** Make the standard catalogue (idempotent). Returns how many were added. */
    public function installStandard(): int
    {
        $added = 0;
        foreach (KpiCatalog::standard() as $d) {
            $kpi = AnalyticsKpi::query()->whereNull('organization_id')->where('code', $d['code'])->first();
            if ($kpi) {
                continue;
            }
            $kpi = AnalyticsKpi::create(['organization_id' => null, 'code' => $d['code'], 'name' => $d['name'], 'description' => $d['description'], 'domain' => $d['domain'], 'unit' => $d['unit'], 'good_direction' => $d['good'], 'definition' => ['fact' => $d['fact'], 'measure' => $d['measure'], 'filters' => $d['filters']],
                'target' => $d['target'], 'version' => 1, 'status' => 'active', 'owner' => 'Management']);
            $this->snapshot($kpi, null, 'Standard KPI');
            $added++;
        }

        return $added;
    }

    private function snapshot(AnalyticsKpi $kpi, ?User $by, ?string $note): void
    {
        AnalyticsKpiVersion::create(['kpi_id' => $kpi->id, 'version' => $kpi->version, 'definition' => $kpi->definition, 'name' => $kpi->name, 'unit' => $kpi->unit, 'target' => $kpi->target, 'note' => $note, 'changed_by' => $by?->id, 'changed_at' => now()]);
    }

    /** The standard KPIs and the hospital's own, newest definition of each. @return Collection<int,AnalyticsKpi> */
    public function forOrganization(int $organizationId): Collection
    {
        return AnalyticsKpi::query()->where('status', 'active')->where(fn ($q) => $q->whereNull('organization_id')->orWhere('organization_id', $organizationId))->orderBy('domain')->orderBy('name')->get();
    }

    public function targetFor(AnalyticsKpi $kpi, int $organizationId): ?string
    {
        $own = AnalyticsKpiTarget::query()->where(['organization_id' => $organizationId, 'kpi_id' => $kpi->id])->value('target');

        return $own !== null ? (string) $own : ($kpi->target !== null ? (string) $kpi->target : null);
    }

    public function setTarget(AnalyticsKpi $kpi, int $organizationId, ?string $target, User $by): void
    {
        if ($target === null || $target === '') {
            AnalyticsKpiTarget::query()->where(['organization_id' => $organizationId, 'kpi_id' => $kpi->id])->delete();

            return;
        }
        if (! is_numeric($target)) {
            throw ValidationException::withMessages(['target' => 'The target must be a number.']);
        }
        AnalyticsKpiTarget::query()->updateOrCreate(['organization_id' => $organizationId, 'kpi_id' => $kpi->id], ['target' => $target, 'updated_by' => $by->id]);
        $this->audit->log('ANALYTICS_KPI_TARGET', AnalyticsKpi::class, $kpi->id, null, ['code' => $kpi->code, 'target' => $target, 'organization' => $organizationId]);
    }

    /**
     * A hospital's own KPI, or a new version of one of its own: the definition is checked against the semantic model, the old definition stays in the history.
     *
     * @param  array{code?:string,name:string,description?:?string,domain:string,unit:string,good_direction:string,fact:string,measure:string,filters?:array,target?:?string,note?:?string}  $d
     */
    public function save(int $organizationId, array $d, User $by, ?AnalyticsKpi $existing = null): AnalyticsKpi
    {
        if ($existing && $existing->organization_id !== $organizationId) {
            throw ValidationException::withMessages(['kpi' => 'A standard KPI cannot be edited; set your own target instead, or make your own KPI.']);
        }
        $definition = ['fact' => $d['fact'], 'measure' => $d['measure'], 'filters' => $d['filters'] ?? []];
        $this->queries->normalise(['fact' => $d['fact'], 'measures' => [$d['measure']], 'filters' => $definition['filters']]); // throws when the definition is not in the model
        if (! in_array($d['unit'], ['count', 'percent', 'minutes', 'days', 'hours', 'money', 'ratio'], true) || ! in_array($d['good_direction'], ['up', 'down', 'none'], true)) {
            throw ValidationException::withMessages(['unit' => 'Choose a unit and which direction is good.']);
        }
        if ($existing === null) {
            $code = preg_replace('/[^a-z0-9_]/', '_', strtolower((string) ($d['code'] ?? $d['name'])));
            if (AnalyticsKpi::query()->where('organization_id', $organizationId)->where('code', $code)->exists()) {
                throw ValidationException::withMessages(['code' => 'You already have a KPI with that code.']);
            }
            $kpi = AnalyticsKpi::create(['organization_id' => $organizationId, 'code' => $code, 'name' => $d['name'], 'description' => $d['description'] ?? null, 'domain' => $d['domain'], 'unit' => $d['unit'], 'good_direction' => $d['good_direction'], 'definition' => $definition,
                'target' => ($d['target'] ?? '') !== '' ? $d['target'] : null, 'version' => 1, 'status' => 'active', 'owner' => $by->name, 'updated_by' => $by->id]);
            $this->snapshot($kpi, $by, $d['note'] ?? 'Created');

            return $kpi;
        }
        $existing->update(['name' => $d['name'], 'description' => $d['description'] ?? $existing->description, 'domain' => $d['domain'], 'unit' => $d['unit'], 'good_direction' => $d['good_direction'], 'definition' => $definition,
            'target' => ($d['target'] ?? '') !== '' ? $d['target'] : null, 'version' => $existing->version + 1, 'updated_by' => $by->id]);
        $this->snapshot($existing->refresh(), $by, $d['note'] ?? null);
        $this->audit->log('ANALYTICS_KPI_CHANGED', AnalyticsKpi::class, $existing->id, null, ['code' => $existing->code, 'version' => $existing->version]);

        return $existing;
    }

    public function retire(AnalyticsKpi $kpi, int $organizationId, User $by): void
    {
        if ($kpi->organization_id !== $organizationId) {
            throw ValidationException::withMessages(['kpi' => 'A standard KPI cannot be retired.']);
        }
        $kpi->update(['status' => 'retired', 'updated_by' => $by->id]);
    }

    /**
     * The value over [from, to] and over the same number of days just before it.
     *
     * @return array{value:?float,previous:?float,change_pct:?float,target:?float,status:string,unit:string,suppressed:bool,from:string,to:string}
     */
    public function evaluate(AnalyticsKpi $kpi, ScopeContext $scope, ?string $from = null, ?string $to = null): array
    {
        $def = $kpi->definition;
        $base = ['fact' => $def['fact'], 'measures' => [$def['measure']], 'filters' => $def['filters'] ?? []];
        $to ??= CarbonImmutable::now((string) config('analytics.timezone', 'Asia/Dhaka'))->toDateString();
        $from ??= CarbonImmutable::parse($to)->subDays((int) config('analytics.dashboard.default_days', 30) - 1)->toDateString();
        $days = (int) CarbonImmutable::parse($from)->diffInDays(CarbonImmutable::parse($to)) + 1;
        $value = $this->scalar($base, $scope, $from, $to, $def['measure']);
        $previous = $this->scalar($base, $scope, CarbonImmutable::parse($from)->subDays($days)->toDateString(), CarbonImmutable::parse($from)->subDay()->toDateString(), $def['measure']);
        $target = $this->targetFor($kpi, $scope->organizationId);
        $status = 'neutral';
        if ($value['value'] !== null && $target !== null && $kpi->good_direction !== 'none') {
            $ok = $kpi->good_direction === 'up' ? $value['value'] >= (float) $target : $value['value'] <= (float) $target;
            $status = $ok ? 'good' : 'bad';
        }
        $change = $value['value'] !== null && $previous['value'] !== null && $previous['value'] != 0.0 ? round(($value['value'] - $previous['value']) / abs($previous['value']) * 100, 1) : null;

        return ['value' => $value['value'], 'previous' => $previous['value'], 'change_pct' => $change, 'target' => $target !== null ? (float) $target : null, 'status' => $status, 'unit' => $kpi->unit, 'suppressed' => $value['suppressed'], 'from' => $from, 'to' => $to];
    }

    /** @return array{value:?float,suppressed:bool} */
    private function scalar(array $base, ScopeContext $scope, string $from, string $to, string $measure): array
    {
        $r = $this->queries->run($base + ['from' => $from, 'to' => $to], $scope);
        $v = $r['rows'][0][$measure] ?? null;

        return ['value' => $v === null ? null : (float) $v, 'suppressed' => $r['meta']['suppressed'] > 0];
    }

    /** What is in the catalogue for a form: facts and their measures. */
    public function catalogue(): array
    {
        return SemanticCatalog::describe();
    }
}
