<?php

namespace App\Services\Icu;

use App\Models\Icu\IcuEpisode;
use App\Models\Ipd\IpdBed;
use App\Models\Ipd\IpdRoom;
use App\Models\Ipd\IpdWard;
use Illuminate\Support\Collection;

/**
 * Read-only aggregation over Phase 8's own ward/bed/allocation tables plus active IcuEpisode
 * rows — never a second bed ledger (spec §55).
 */
class IcuBedBoardService
{
    public function board(int $companyId, ?int $branchId = null): Collection
    {
        $wardIds = IpdWard::query()->forTenant($companyId, $branchId)->icuTypes()->pluck('id');
        $roomIds = IpdRoom::query()->whereIn('ward_id', $wardIds)->pluck('id');

        $beds = IpdBed::query()
            ->forTenant($companyId, $branchId)
            ->where(fn ($q) => $q->where('icu_capable', true)->orWhereIn('room_id', $roomIds))
            ->with(['room.ward', 'currentAllocation.admission.patient'])
            ->get();

        $activeEpisodesByBed = IcuEpisode::query()
            ->forTenant($companyId, $branchId)
            ->whereNull('discharged_at')
            ->whereNotNull('icu_bed_id')
            ->with('patient')
            ->get()
            ->keyBy('icu_bed_id');

        return $beds->map(function (IpdBed $bed) use ($activeEpisodesByBed) {
            $episode = $activeEpisodesByBed->get($bed->id);

            return [
                'bed' => $bed,
                'ward' => $bed->room?->ward,
                'episode' => $episode,
                'patient' => $episode?->patient,
                'status' => $bed->status,
            ];
        });
    }
}
