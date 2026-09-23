<?php

namespace App\Services\Laboratory;

use App\Models\Laboratory\LabOrderItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Pure query service, no new table — filters LabOrderItem by section/test/priority/status/date.
 */
class WorklistService
{
    /**
     * @param  array{section_id?:int,test_id?:int,priority?:string,status?:string,date?:string}  $filters
     */
    public function query(int $companyId, ?int $branchId, array $filters = []): LengthAwarePaginator
    {
        return LabOrderItem::query()
            ->whereHas('labOrder', function ($q) use ($companyId, $branchId) {
                $q->forTenant($companyId, $branchId);
            })
            ->whereIn('status', ['received', 'processing'])
            ->with(['labOrder.patient', 'test.section', 'specimen'])
            ->when($filters['section_id'] ?? null, fn ($q, $v) => $q->whereHas('test', fn ($t) => $t->where('section_id', $v)))
            ->when($filters['test_id'] ?? null, fn ($q, $v) => $q->where('test_id', $v))
            ->when($filters['priority'] ?? null, fn ($q, $v) => $q->where('priority', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['date'] ?? null, fn ($q, $v) => $q->whereDate('requested_at', $v))
            ->orderByRaw("field(priority, 'stat', 'urgent', 'routine')")
            ->orderBy('requested_at')
            ->paginate(30);
    }
}
