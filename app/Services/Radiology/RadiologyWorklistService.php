<?php

namespace App\Services\Radiology;

use App\Models\Radiology\RadiologyExamination;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Pure query service, no new table — filters per spec §36 (date/priority/modality/body part/
 * procedure/accession/department/physician/reporting-status/assigned-radiologist).
 */
class RadiologyWorklistService
{
    /**
     * @param  array{modality_id?:int,priority?:string,status?:string,date?:string,assigned_radiologist_id?:int,unassigned?:bool,accession_number?:string}  $filters
     */
    public function query(int $companyId, ?int $branchId, array $filters = []): LengthAwarePaginator
    {
        return RadiologyExamination::query()
            ->forTenant($companyId, $branchId)
            ->whereIn('radiology_examinations.status', ['completed', 'images_available'])
            ->join('radiology_order_items', 'radiology_order_items.id', '=', 'radiology_examinations.order_item_id')
            ->select('radiology_examinations.*')
            ->with(['orderItem.radiologyOrder.patient', 'orderItem.procedure', 'modality', 'assignedRadiologist'])
            ->when($filters['modality_id'] ?? null, fn ($q, $v) => $q->where('radiology_examinations.modality_id', $v))
            ->when($filters['priority'] ?? null, fn ($q, $v) => $q->where('radiology_order_items.priority', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('radiology_examinations.status', $v))
            ->when($filters['date'] ?? null, fn ($q, $v) => $q->whereDate('radiology_examinations.completed_at', $v))
            ->when($filters['assigned_radiologist_id'] ?? null, fn ($q, $v) => $q->where('radiology_examinations.assigned_radiologist_id', $v))
            ->when($filters['unassigned'] ?? false, fn ($q) => $q->whereNull('radiology_examinations.assigned_radiologist_id'))
            ->when($filters['accession_number'] ?? null, fn ($q, $v) => $q->whereHas('orderItem.radiologyOrder', fn ($sub) => $sub->where('accession_number', 'like', "%{$v}%")))
            // Portable priority ordering (FIELD() is MySQL-only and breaks on the SQLite test
            // suite the moment real rows reach this ORDER BY — CASE WHEN works on both).
            ->orderByRaw("case radiology_order_items.priority when 'stat' then 0 when 'urgent' then 1 else 2 end")
            ->orderBy('radiology_examinations.completed_at')
            ->paginate(30);
    }
}
