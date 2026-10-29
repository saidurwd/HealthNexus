<?php

namespace App\Services\Analytics\Semantic;

use App\Services\Analytics\Warehouse;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\ValidationException;

/**
 * Compiles a semantic query — {fact, measures[], group_by[], grain, filters[], from, to, order_by, direction, limit} — into one parameterised SELECT on the warehouse and runs it.
 * Users choose KEYS from the catalogue; every identifier and expression in the SQL comes from the catalogue, every value is a bound parameter. The scope (hospital, branches) is added here.
 */
class SemanticQuery
{
    public const OPS = ['=', '!=', 'in', 'not_in', '>', '>=', '<', '<='];

    public const GRAINS = ['day', 'month', 'year'];

    public function __construct(private readonly Warehouse $warehouse) {}

    /**
     * @param  array<string,mixed>  $q
     * @return array{columns:list<array{key:string,label:string,kind:string,unit:?string}>,rows:list<array<string,mixed>>,meta:array<string,mixed>}
     */
    public function run(array $q, ScopeContext $scope): array
    {
        $q = $this->normalise($q);
        $fact = SemanticCatalog::fact($q['fact']);
        $built = $this->build($q, $fact, $scope);
        if ($built === null) { // the user may not see this fact at all (branch-limited user, fact kept per hospital)
            return ['columns' => $this->columns($q, $fact), 'rows' => [], 'meta' => ['fact' => $q['fact'], 'sql' => '', 'suppressed' => 0, 'rows' => 0, 'note' => 'This figure is kept for the whole hospital and is not shown for a single branch.', 'from' => $q['from'], 'to' => $q['to']]];
        }
        [$query, $sql] = $built;
        $suppressed = 0;
        $rows = [];
        foreach ($query->get() as $row) {
            $out = [];
            foreach ((array) $row as $k => $v) {
                $out[$k] = $v;
            }
            foreach ($q['measures'] as $m) {
                $spec = $fact['measures'][$m];
                $v = $out[$m] ?? null;
                $v = $v === null ? null : ($spec['unit'] === 'count' ? (int) round((float) $v) : round((float) $v, 2));
                if ($v !== null && ! empty($spec['small']) && ! $scope->canSeeSmallNumbers && $v > 0 && $v < (int) config('analytics.small_cell', 5)) {
                    $v = null;
                    $suppressed++;
                }
                $out[$m] = $v;
            }
            $rows[] = $out;
        }

        return ['columns' => $this->columns($q, $fact), 'rows' => $rows, 'meta' => ['fact' => $q['fact'], 'sql' => $sql, 'suppressed' => $suppressed, 'rows' => count($rows), 'from' => $q['from'], 'to' => $q['to']]];
    }

    /** @param array<string,mixed> $q @return list<array{key:string,label:string,kind:string,unit:?string}> */
    private function columns(array $q, array $fact): array
    {
        $cols = [];
        if ($q['grain']) {
            $cols[] = ['key' => 'period', 'label' => 'Period', 'kind' => 'dimension', 'unit' => null];
        }
        foreach ($q['group_by'] as $d) {
            $cols[] = ['key' => $d, 'label' => $fact['dimensions'][$d]['label'], 'kind' => 'dimension', 'unit' => null];
        }
        foreach ($q['measures'] as $m) {
            $cols[] = ['key' => $m, 'label' => $fact['measures'][$m]['label'], 'kind' => 'measure', 'unit' => $fact['measures'][$m]['unit']];
        }

        return $cols;
    }

    /** Check the query against the catalogue and fill defaults. @param array<string,mixed> $q @return array<string,mixed> */
    public function normalise(array $q): array
    {
        $fail = fn (string $field, string $msg) => throw ValidationException::withMessages([$field => $msg]);
        $fact = SemanticCatalog::fact((string) ($q['fact'] ?? ''));
        if ($fact === null) {
            $fail('fact', 'Choose what to look at.');
        }
        $measures = array_values(array_unique((array) ($q['measures'] ?? [])));
        if ($measures === [] || count($measures) > 6) {
            $fail('measures', 'Choose between one and six figures.');
        }
        foreach ($measures as $m) {
            if (! is_string($m) || ! isset($fact['measures'][$m])) {
                $fail('measures', 'That figure is not available for this subject.');
            }
        }
        $snapshots = array_filter($measures, fn ($m) => ! empty($fact['measures'][$m]['snapshot']));
        if ($snapshots !== [] && count($snapshots) !== count($measures)) {
            $fail('measures', 'A balance as of a date cannot be mixed with totals over a period.');
        }
        $groupBy = array_values(array_unique((array) ($q['group_by'] ?? [])));
        if (count($groupBy) > 2) {
            $fail('group_by', 'Group by at most two things.');
        }
        foreach ($groupBy as $d) {
            if (! is_string($d) || ! isset($fact['dimensions'][$d])) {
                $fail('group_by', 'That way of cutting is not available for this subject.');
            }
        }
        $grain = $q['grain'] ?? null;
        if ($grain !== null && ! in_array($grain, self::GRAINS, true)) {
            $fail('grain', 'Choose day, month or year.');
        }
        if ($grain === 'day' && $fact['date']['type'] === 'ym') {
            $fail('grain', 'This subject is kept by month.');
        }
        $filters = [];
        foreach ((array) ($q['filters'] ?? []) as $f) {
            $d = $f['dimension'] ?? null;
            $op = $f['op'] ?? '=';
            if (! is_string($d) || ! isset($fact['dimensions'][$d])) {
                $fail('filters', 'A filter uses a way of cutting this subject does not have.');
            }
            if (! in_array($op, self::OPS, true)) {
                $fail('filters', 'Unknown filter operator.');
            }
            $value = $f['value'] ?? null;
            if (in_array($op, ['in', 'not_in'], true)) {
                $value = array_values(array_filter((array) $value, fn ($v) => is_scalar($v)));
                if ($value === [] || count($value) > 50) {
                    $fail('filters', 'A list filter needs between one and fifty values.');
                }
            } elseif (! is_scalar($value)) {
                $fail('filters', 'A filter value must be a single value.');
            }
            $filters[] = ['dimension' => $d, 'op' => $op, 'value' => $value];
        }
        $to = isset($q['to']) && $q['to'] ? CarbonImmutable::parse($q['to'])->toDateString() : CarbonImmutable::now((string) config('analytics.timezone', 'Asia/Dhaka'))->toDateString();
        $from = isset($q['from']) && $q['from'] ? CarbonImmutable::parse($q['from'])->toDateString() : CarbonImmutable::parse($to)->subDays((int) config('analytics.dashboard.default_days', 30) - 1)->toDateString();
        if ($from > $to) {
            $fail('from', 'The period ends before it starts.');
        }
        $order = $q['order_by'] ?? null;
        $allowedOrder = array_merge($measures, $groupBy, $grain ? ['period'] : []);
        if ($order !== null && ! in_array($order, $allowedOrder, true)) {
            $fail('order_by', 'You can only sort by something that is shown.');
        }
        $limit = max(1, min(1000, (int) ($q['limit'] ?? 200)));

        return ['fact' => $q['fact'], 'measures' => $measures, 'group_by' => $groupBy, 'grain' => $grain, 'filters' => $filters, 'from' => $from, 'to' => $to, 'order_by' => $order, 'direction' => ($q['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc', 'limit' => $limit];
    }

    /** @param array<string,mixed> $q @return array{0:Builder,1:string}|null */
    private function build(array $q, array $fact, ScopeContext $scope): ?array
    {
        $hasBranch = isset($fact['scope']['branch']);
        if (! $hasBranch && $scope->branchIds !== null) {
            return null;
        }
        $query = $this->warehouse->table($fact['table'].' as f');
        $bindings = [];
        $select = [];
        $group = [];
        $joinN = 0;
        $isKey = $fact['date']['type'] === 'key';

        if ($q['grain']) {
            if ($isKey) {
                $query->join('dim_date as dd', 'dd.date_key', '=', 'f.'.$fact['date']['col']);
                $expr = ['day' => 'dd.date', 'month' => 'dd.year_month', 'year' => 'dd.year'][$q['grain']];
            } else {
                $expr = $q['grain'] === 'month' ? 'f.'.$fact['date']['col'] : 'substr(f.'.$fact['date']['col'].', 1, 4)';
            }
            $select[] = "$expr as period";
            $group[] = $expr;
        }
        foreach ($q['group_by'] as $d) {
            $spec = $fact['dimensions'][$d];
            if (isset($spec['join'])) {
                [$table, $key, $name] = $spec['join'];
                $alias = 'dj'.(++$joinN);
                $query->leftJoin("$table as $alias", "$alias.$key", '=', 'f.'.$spec['col']);
                $select[] = "$alias.$name as $d";
                $select[] = 'f.'.$spec['col']." as {$d}_id";
                $group[] = "$alias.$name";
                $group[] = 'f.'.$spec['col'];
            } else {
                $select[] = 'f.'.$spec['col']." as $d";
                $group[] = 'f.'.$spec['col'];
            }
        }
        foreach ($q['measures'] as $m) {
            $select[] = $this->measureSql($fact, $m)." as $m";
        }
        $query->selectRaw(implode(', ', $select));

        // scope: the hospital and branches come from the signed-in user
        $query->where('f.'.$fact['scope']['organization'], $scope->organizationId);
        if ($hasBranch && $scope->branchIds !== null) {
            $scope->branchIds === [] ? $query->whereRaw('1 = 0') : $query->whereIn('f.'.$fact['scope']['branch'], $scope->branchIds);
        }
        // period
        if ($isKey) {
            $query->whereBetween('f.'.$fact['date']['col'], [(int) str_replace('-', '', $q['from']), (int) str_replace('-', '', $q['to'])]);
        } else {
            $query->whereBetween('f.'.$fact['date']['col'], [substr($q['from'], 0, 7), substr($q['to'], 0, 7)]);
        }
        if (isset($fact['base'])) {
            $query->whereRaw($fact['base']);
        }
        if (array_filter($q['measures'], fn ($m) => ! empty($fact['measures'][$m]['snapshot']))) { // a balance: the last month in range
            $query->whereRaw('f.'.$fact['date']['col'].' = (select max(x.'.$fact['date']['col'].') from '.$fact['table'].' x where x.'.$fact['scope']['organization'].' = f.'.$fact['scope']['organization'].' and x.'.$fact['date']['col'].' <= ?)', [substr($q['to'], 0, 7)]);
        }
        foreach ($q['filters'] as $f) {
            $col = 'f.'.$fact['dimensions'][$f['dimension']]['col'];
            match ($f['op']) {
                'in' => $query->whereIn($col, $f['value']), 'not_in' => $query->whereNotIn($col, $f['value']), default => $query->where($col, $f['op'], $f['value']),
            };
        }
        $group !== [] && $query->groupByRaw(implode(', ', array_unique($group)));
        $orderKey = $q['order_by'] ?? ($q['grain'] ? 'period' : $q['measures'][0]);
        $direction = $q['order_by'] ? $q['direction'] : ($q['grain'] ? 'asc' : 'desc');
        $query->orderByRaw("$orderKey $direction");
        $query->limit($q['limit']);

        return [$query, $this->display($query->toSql(), $query->getBindings())];
    }

    /** @param array<string,mixed> $fact */
    private function measureSql(array $fact, string $key, int $depth = 0): string
    {
        $m = $fact['measures'][$key];
        $when = $m['when'] ?? null;
        switch ($m['agg']) {
            case 'count':
                return $when ? "coalesce(sum(case when $when then 1 else 0 end), 0)" : 'count(*)';
            case 'sum':
                return $when ? "coalesce(sum(case when $when then f.{$m['col']} else 0 end), 0)" : "coalesce(sum(f.{$m['col']}), 0)";
            case 'avg':
                return $when ? "avg(case when $when then f.{$m['col']} end)" : "avg(f.{$m['col']})";
            case 'distinct':
                return "count(distinct f.{$m['col']})";
            case 'expr':
                return "coalesce(sum({$m['expr']}), 0)";
            case 'ratio':
                return '(('.$this->measureSql($fact, $m['num'], $depth + 1).') * '.(float) ($m['scale'] ?? 1).' / nullif(('.$this->measureSql($fact, $m['den'], $depth + 1).') * 1.0, 0))';
        }
        throw new \LogicException("Unknown measure kind {$m['agg']}.");
    }

    /** The query as the person asking would read it (values filled in). It is for showing only; it is never run. @param list<mixed> $bindings */
    private function display(string $sql, array $bindings): string
    {
        foreach ($bindings as $b) {
            $sql = preg_replace('/\?/', is_numeric($b) ? (string) $b : "'".str_replace("'", "''", (string) $b)."'", $sql, 1);
        }

        return $sql;
    }
}
