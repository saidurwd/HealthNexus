<?php

namespace App\Services\Analytics\Loaders;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Laboratory orders with their turn-around times (order → first sample taken, order → final report) and counts of abnormal and critical results. Orders of the last week are read again because results arrive after the order was last touched. */
class LabOrderFact extends Loader
{
    public function key(): string
    {
        return 'fact_lab_orders';
    }

    public function label(): string
    {
        return 'Laboratory orders';
    }

    protected function target(): string
    {
        return 'fact_lab_orders';
    }

    protected function uniqueBy(): array
    {
        return ['lab_order_id'];
    }

    protected function source(): Builder
    {
        return DB::table('lab_orders')->select('lab_orders.id', 'lab_orders.organization_id', 'lab_orders.branch_id', 'lab_orders.department_id', 'lab_orders.patient_id', 'lab_orders.priority', 'lab_orders.status', 'lab_orders.ordered_at', 'lab_orders.updated_at');
    }

    protected function watermarkColumn(): string
    {
        return 'lab_orders.updated_at';
    }

    protected function idColumn(): string
    {
        return 'lab_orders.id';
    }

    protected function windowDays(): int
    {
        return 7;
    }

    protected function windowColumn(): ?string
    {
        return 'lab_orders.ordered_at';
    }

    protected function map(Collection $rows): array
    {
        $ids = $rows->pluck('id')->all();
        $items = DB::table('lab_order_items')->whereIn('lab_order_id', $ids)->groupBy('lab_order_id')->selectRaw('lab_order_id, count(*) as n, min(collected_at) as first_collected')->get()->keyBy('lab_order_id');
        $reports = DB::table('lab_reports')->whereIn('lab_order_id', $ids)->whereIn('status', ['final', 'amended'])->groupBy('lab_order_id')->selectRaw('lab_order_id, min(generated_at) as reported_at')->get()->keyBy('lab_order_id');
        $results = DB::table('lab_results')->join('lab_order_items', 'lab_order_items.id', '=', 'lab_results.lab_order_item_id')->whereIn('lab_order_items.lab_order_id', $ids)->where('lab_results.is_current', true)
            ->groupBy('lab_order_items.lab_order_id')->selectRaw('lab_order_items.lab_order_id as oid, sum(case when lab_results.critical_flag = 1 then 1 else 0 end) as critical, sum(case when lab_results.abnormal_flag is not null and lab_results.abnormal_flag <> \'\' and lab_results.abnormal_flag <> \'N\' then 1 else 0 end) as abnormal')->get()->keyBy('oid');
        $keys = $this->patientKeys($rows, fn ($r) => $this->localDay($r->ordered_at));

        return $rows->values()->map(function ($r, $i) use ($items, $reports, $results, $keys) {
            $reportedAt = $reports[$r->id]->reported_at ?? null;

            return ['lab_order_id' => $r->id, 'organization_id' => $r->organization_id, 'branch_id' => $r->branch_id, 'date_key' => (int) str_replace('-', '', $this->localDay($r->ordered_at) ?? '19700101'), 'department_id' => $r->department_id, 'patient_key' => $keys[$i],
                'priority' => $r->priority, 'status' => $r->status, 'items' => (int) ($items[$r->id]->n ?? 0), 'tat_collect_min' => $this->minutes($r->ordered_at, $items[$r->id]->first_collected ?? null), 'tat_report_min' => $this->minutes($r->ordered_at, $reportedAt),
                'critical_results' => (int) ($results[$r->id]->critical ?? 0), 'abnormal_results' => (int) ($results[$r->id]->abnormal ?? 0), 'reported' => $reportedAt !== null];
        })->all();
    }
}
