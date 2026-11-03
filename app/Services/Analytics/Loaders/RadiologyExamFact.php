<?php

namespace App\Services\Analytics\Loaders;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Imaging examinations with the time from order to approved report. */
class RadiologyExamFact extends Loader
{
    public function key(): string
    {
        return 'fact_radiology_exams';
    }

    public function label(): string
    {
        return 'Imaging examinations';
    }

    protected function target(): string
    {
        return 'fact_radiology_exams';
    }

    protected function uniqueBy(): array
    {
        return ['examination_id'];
    }

    protected function source(): Builder
    {
        return DB::table('radiology_examinations')->join('radiology_order_items', 'radiology_order_items.id', '=', 'radiology_examinations.order_item_id')->join('radiology_orders', 'radiology_orders.id', '=', 'radiology_order_items.radiology_order_id')
            ->select('radiology_examinations.id', 'radiology_examinations.organization_id', 'radiology_examinations.branch_id', 'radiology_examinations.patient_id', 'radiology_examinations.modality_id', 'radiology_examinations.status', 'radiology_examinations.updated_at', 'radiology_orders.priority', 'radiology_orders.ordered_at');
    }

    protected function watermarkColumn(): string
    {
        return 'radiology_examinations.updated_at';
    }

    protected function idColumn(): string
    {
        return 'radiology_examinations.id';
    }

    protected function windowDays(): int
    {
        return 7;
    }

    protected function windowColumn(): ?string
    {
        return 'radiology_orders.ordered_at';
    }

    protected function map(Collection $rows): array
    {
        $approved = DB::table('radiology_reports')->whereIn('examination_id', $rows->pluck('id')->all())->where('is_current', true)->whereIn('status', ['final', 'amended'])->pluck('approved_at', 'examination_id');
        $keys = $this->patientKeys($rows, fn ($r) => $this->localDay($r->ordered_at));

        return $rows->values()->map(fn ($r, $i) => ['examination_id' => $r->id, 'organization_id' => $r->organization_id, 'branch_id' => $r->branch_id, 'date_key' => (int) str_replace('-', '', $this->localDay($r->ordered_at) ?? '19700101'), 'modality_id' => $r->modality_id,
            'patient_key' => $keys[$i], 'priority' => $r->priority, 'status' => $r->status, 'tat_report_min' => $this->minutes($r->ordered_at, $approved[$r->id] ?? null), 'reported' => isset($approved[$r->id])])->all();
    }
}
