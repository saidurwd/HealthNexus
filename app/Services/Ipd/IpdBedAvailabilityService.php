<?php

namespace App\Services\Ipd;

use App\Models\Ipd\IpdBed;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Search/filter surface backing both the bed board (spec §41) and bed availability search
 * (spec §40). Always server-side scoped by company/branch — never trusts a client-supplied
 * hospital/branch id beyond what TenantContextResolver already resolved for the request.
 */
class IpdBedAvailabilityService
{
    /**
     * @param  array{building_id?:int,floor_id?:int,ward_id?:int,room_id?:int,bed_type_id?:int,gender?:string,isolation?:bool,status?:string,search?:string}  $filters
     */
    public function search(int $companyId, ?int $branchId, array $filters = [], int $perPage = 30): LengthAwarePaginator
    {
        return IpdBed::query()
            ->forTenant($companyId, $branchId)
            ->where('ipd_beds.is_active', true)
            ->with(['room.ward.floor', 'room.ward.building', 'bedType', 'currentAllocation.admission.patient'])
            ->whereHas('room', function ($q) use ($filters) {
                $q->when($filters['ward_id'] ?? null, fn ($w) => $w->where('ward_id', $filters['ward_id']));
                $q->when($filters['room_id'] ?? null, fn ($w) => $w->where('id', $filters['room_id']));

                if (! empty($filters['building_id']) || ! empty($filters['floor_id'])) {
                    $q->whereHas('ward', function ($wardQuery) use ($filters) {
                        $wardQuery->when($filters['building_id'] ?? null, fn ($b) => $b->where('building_id', $filters['building_id']));
                        $wardQuery->when($filters['floor_id'] ?? null, fn ($b) => $b->where('floor_id', $filters['floor_id']));
                    });
                }
            })
            ->when($filters['bed_type_id'] ?? null, fn ($q) => $q->where('ipd_beds.bed_type_id', $filters['bed_type_id']))
            ->when($filters['gender'] ?? null, fn ($q) => $q->where(fn ($g) => $g->where('ipd_beds.gender_type', $filters['gender'])->orWhere('ipd_beds.gender_type', 'any')))
            ->when(array_key_exists('isolation', $filters), fn ($q) => $q->where('ipd_beds.isolation_capable', (bool) $filters['isolation']))
            ->when($filters['status'] ?? null, fn ($q) => $q->where('ipd_beds.status', $filters['status']))
            ->when($filters['search'] ?? null, fn ($q) => $q->where(fn ($s) => $s
                ->where('ipd_beds.bed_code', 'like', '%'.$filters['search'].'%')
                ->orWhere('ipd_beds.bed_name', 'like', '%'.$filters['search'].'%')))
            ->orderBy('room_id')
            ->orderBy('bed_code')
            ->paginate($perPage);
    }

    public function availableCount(int $companyId, ?int $branchId, array $filters = []): int
    {
        return $this->search($companyId, $branchId, [...$filters, 'status' => IpdBed::STATUS_AVAILABLE], 1)->total();
    }
}
