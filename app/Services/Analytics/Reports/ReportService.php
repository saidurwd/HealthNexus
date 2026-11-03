<?php

namespace App\Services\Analytics\Reports;

use App\Models\Analytics\AnalyticsReport;
use App\Models\User;
use App\Services\Analytics\Semantic\ScopeContext;
use App\Services\Analytics\Semantic\SemanticQuery;
use App\Services\AuditLogger;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/** Saved reports: a name and a semantic query. Running one always uses the scope of the person running it. */
class ReportService
{
    public function __construct(private readonly SemanticQuery $queries, private readonly AuditLogger $audit) {}

    /** @param array<string,mixed> $query */
    public function save(int $organizationId, User $by, string $name, ?string $description, array $query, bool $shared, ?AnalyticsReport $existing = null): AnalyticsReport
    {
        if (trim($name) === '') {
            throw ValidationException::withMessages(['name' => 'Give the report a name.']);
        }
        $this->queries->normalise($query + ['from' => null, 'to' => null]); // throws when the query is not in the model
        $clean = array_intersect_key($query, array_flip(['fact', 'measures', 'group_by', 'grain', 'filters', 'order_by', 'direction', 'limit']));
        if ($existing) {
            if ($existing->organization_id !== $organizationId || ($existing->created_by !== $by->id && ! $by->can('analytics.report.manage'))) {
                throw ValidationException::withMessages(['report' => 'This report cannot be changed by you.']);
            }
            $existing->update(['name' => mb_substr(trim($name), 0, 120), 'description' => $description, 'query' => $clean, 'shared' => $shared]);

            return $existing;
        }

        return AnalyticsReport::create(['organization_id' => $organizationId, 'name' => mb_substr(trim($name), 0, 120), 'description' => $description, 'query' => $clean, 'shared' => $shared, 'created_by' => $by->id]);
    }

    /** @return Collection<int,AnalyticsReport> */
    public function visibleTo(User $user, int $organizationId): Collection
    {
        return AnalyticsReport::query()->where('organization_id', $organizationId)->where(fn ($q) => $q->where('shared', true)->orWhere('created_by', $user->id))->orderBy('name')->get();
    }

    public function delete(AnalyticsReport $report, User $by): void
    {
        if ($report->created_by !== $by->id && ! $by->can('analytics.report.manage')) {
            throw ValidationException::withMessages(['report' => 'This report cannot be deleted by you.']);
        }
        $report->delete();
    }

    /** @return array{columns:list<array<string,mixed>>,rows:list<array<string,mixed>>,meta:array<string,mixed>} */
    public function run(AnalyticsReport $report, ScopeContext $scope, ?string $from = null, ?string $to = null): array
    {
        return $this->queries->run($report->query + array_filter(['from' => $from, 'to' => $to]), $scope);
    }

    /** CSV text. A cell that starts with = + - @ is quoted so a spreadsheet does not run it. @param array{columns:list<array<string,mixed>>,rows:list<array<string,mixed>>} $result */
    public function toCsv(array $result): string
    {
        $safe = fn ($v) => is_string($v) && $v !== '' && in_array($v[0], ['=', '+', '-', '@'], true) && ! is_numeric($v) ? "'".$v : $v;
        $out = fopen('php://temp', 'r+');
        fputcsv($out, array_map(fn ($c) => $c['label'], $result['columns']));
        foreach ($result['rows'] as $row) {
            fputcsv($out, array_map(fn ($c) => $safe($row[$c['key']] ?? ''), $result['columns']));
        }
        rewind($out);

        return (string) stream_get_contents($out);
    }

    /** A plain printable page for the PDF renderer. @param array{columns:list<array<string,mixed>>,rows:list<array<string,mixed>>,meta:array<string,mixed>} $result */
    public function toHtml(string $title, array $result): string
    {
        $cell = fn ($v) => e(is_float($v) ? number_format($v, 2) : (string) ($v ?? '—'));
        $head = implode('', array_map(fn ($c) => '<th style="text-align:left;border-bottom:1px solid #444;padding:4px">'.e($c['label']).'</th>', $result['columns']));
        $body = '';
        foreach ($result['rows'] as $row) {
            $body .= '<tr>'.implode('', array_map(fn ($c) => '<td style="padding:3px 4px;border-bottom:1px solid #ddd">'.$cell($row[$c['key']] ?? null).'</td>', $result['columns'])).'</tr>';
        }

        return '<html><head><meta charset="utf-8"><style>body{font-family:sans-serif;font-size:11px}</style></head><body><h2>'.e($title).'</h2><p>'.e($result['meta']['from'] ?? '').' – '.e($result['meta']['to'] ?? '').'</p><table style="border-collapse:collapse;width:100%"><thead><tr>'.$head.'</tr></thead><tbody>'.$body.'</tbody></table>'
            .($result['meta']['suppressed'] ?? 0 ? '<p><small>Small numbers are hidden.</small></p>' : '').'<p><small>Generated '.e(now()->setTimezone('Asia/Dhaka')->format('d M Y H:i')).'</small></p></body></html>';
    }

    public function auditRun(AnalyticsReport $report, User $by, string $how, int $rows): void
    {
        $this->audit->log('ANALYTICS_REPORT_'.strtoupper($how), AnalyticsReport::class, $report->id, null, ['report' => $report->name, 'rows' => $rows, 'by' => $by->id]);
    }
}
