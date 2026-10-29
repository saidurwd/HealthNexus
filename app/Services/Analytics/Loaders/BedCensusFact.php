<?php

namespace App\Services\Analytics\Loaders;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * The midnight census: for every day, the beds in service and the beds occupied at the end of the day, by branch. Rebuilt from the bed allocations each run (the history of bed numbers
 * is not kept operationally, so the bed total is today's count of active beds).
 */
class BedCensusFact extends Loader
{
    public function key(): string
    {
        return 'fact_bed_census';
    }

    public function label(): string
    {
        return 'Bed census';
    }

    public function mode(): string
    {
        return 'snapshot';
    }

    protected function target(): string
    {
        return 'fact_bed_census';
    }

    protected function uniqueBy(): array
    {
        return ['date_key', 'organization_id', 'branch_id'];
    }

    public function load(?string $watermark): array
    {
        $tz = (string) config('analytics.timezone', 'Asia/Dhaka');
        $first = DB::table('ipd_bed_allocations')->min('allocated_at');
        if ($first === null) {
            return ['read' => 0, 'written' => 0, 'watermark' => null];
        }
        $start = CarbonImmutable::parse($first, 'UTC')->setTimezone($tz)->startOfDay()->max(CarbonImmutable::now($tz)->subDays(400)->startOfDay());
        $today = CarbonImmutable::now($tz)->startOfDay();
        $branches = DB::table('ipd_beds')->where('is_active', true)->groupBy('organization_id', 'branch_id')->selectRaw('organization_id, branch_id, count(*) as beds')->get();
        $rows = [];
        for ($day = $start; $day->lte($today); $day = $day->addDay()) {
            $midnight = $day->addDay()->startOfDay()->setTimezone('UTC')->toDateTimeString(); // the end of this local day, in stored (UTC) time
            foreach ($branches as $b) {
                $occupied = DB::table('ipd_bed_allocations')->where('organization_id', $b->organization_id)->when($b->branch_id === null, fn ($q) => $q->whereNull('branch_id'), fn ($q) => $q->where('branch_id', $b->branch_id))->where('allocated_at', '<', $midnight)
                    ->where(fn ($q) => $q->whereNull('released_at')->orWhere('released_at', '>=', $midnight))->distinct()->count('bed_id');
                $rows[] = ['date_key' => (int) $day->format('Ymd'), 'organization_id' => $b->organization_id, 'branch_id' => (int) $b->branch_id, 'beds_total' => $b->beds, 'beds_occupied' => min($occupied, $b->beds), 'loaded_at' => now()->toDateTimeString()];
            }
        }
        $written = $this->warehouse->upsert('fact_bed_census', $rows, $this->uniqueBy());

        return ['read' => count($rows), 'written' => $written, 'watermark' => $today->toDateString()];
    }
}
