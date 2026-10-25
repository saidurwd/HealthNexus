<?php

namespace App\Services\Icu;

use App\Models\Icu\IcuEpisode;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Read-side aggregation, not a stored table (Phase 11 plan decision #6) — the spec's flowsheet
 * (§11) is a display grid over data that already lives in icu_observations/icu_ventilator_events/
 * icu_infusions/icu_devices/icu_fluid_balance_entries. Storing it separately would duplicate data
 * already there and risk drift.
 */
class IcuFlowsheetService
{
    /**
     * @return Collection<string, array{observations: Collection, ventilator: Collection, infusions: Collection, devices: Collection, fluid_balance: Collection}>
     */
    public function build(IcuEpisode $episode, Carbon $from, Carbon $to, string $interval = 'hourly'): Collection
    {
        $bucketMinutes = match ($interval) {
            'hourly' => 60,
            '2hourly' => 120,
            '4hourly' => 240,
            'shift' => 480,
            'daily' => 1440,
            default => 60,
        };

        $observations = $episode->observations()->whereBetween('observed_at', [$from, $to])->orderBy('observed_at')->get();
        $ventilatorEvents = $episode->ventilatorEvents()->whereBetween('recorded_at', [$from, $to])->orderBy('recorded_at')->get();
        $infusions = $episode->infusions()->where('started_at', '<=', $to)->where(fn ($q) => $q->whereNull('stopped_at')->orWhere('stopped_at', '>=', $from))->get();
        $devices = $episode->devices()->where('insertion_date', '<=', $to)->get();
        $fluidEntries = \App\Models\Icu\IcuFluidBalanceEntry::where('episode_id', $episode->id)->whereBetween('recorded_at', [$from, $to])->get();

        $buckets = collect();
        $cursor = $from->copy();

        while ($cursor->lt($to)) {
            $bucketEnd = $cursor->copy()->addMinutes($bucketMinutes);
            $key = $cursor->format('Y-m-d H:i');

            $buckets->put($key, [
                'observations' => $observations->filter(fn ($o) => $o->observed_at->between($cursor, $bucketEnd)),
                'ventilator' => $ventilatorEvents->filter(fn ($v) => $v->recorded_at->between($cursor, $bucketEnd)),
                'infusions' => $infusions,
                'devices' => $devices,
                'fluid_balance' => $fluidEntries->filter(fn ($f) => $f->recorded_at->between($cursor, $bucketEnd)),
            ]);

            $cursor = $bucketEnd;
        }

        return $buckets;
    }
}
