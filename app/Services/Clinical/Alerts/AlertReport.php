<?php

namespace App\Services\Clinical\Alerts;

use App\Models\Clinical\ClinicalAlert;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/** Acknowledgement-latency report (H-04 done-when): per category, how fast alerts are acknowledged and how many escalate. */
class AlertReport
{
    /** @return Collection<string, array{raised: int, acknowledged: int, unacknowledged: int, escalated: int, avg_ack_seconds: ?int, max_ack_seconds: ?int}> */
    public function latency(int $organizationId, CarbonInterface $from, CarbonInterface $to): Collection
    {
        return ClinicalAlert::query()
            ->where('organization_id', $organizationId)->whereBetween('raised_at', [$from, $to])
            ->get()
            ->groupBy('category')
            ->map(function (Collection $alerts) {
                $latencies = $alerts->whereNotNull('acknowledged_at')
                    ->map(fn (ClinicalAlert $a) => $a->raised_at->diffInSeconds($a->acknowledged_at, true));

                return [
                    'raised' => $alerts->count(),
                    'acknowledged' => $latencies->count(),
                    'unacknowledged' => $alerts->where('status', ClinicalAlert::STATUS_OPEN)->count(),
                    'escalated' => $alerts->where('escalation_level', '>', 0)->count(),
                    'avg_ack_seconds' => $latencies->isEmpty() ? null : (int) round($latencies->avg()),
                    'max_ack_seconds' => $latencies->isEmpty() ? null : (int) $latencies->max(),
                ];
            });
    }

    /** Everything still waiting for a human, worst first. */
    public function unacknowledged(int $organizationId): Collection
    {
        return ClinicalAlert::query()
            ->where('organization_id', $organizationId)->where('status', ClinicalAlert::STATUS_OPEN)->where('ack_required', true)
            ->orderByRaw("case severity when 'critical' then 0 when 'warning' then 1 else 2 end")->orderBy('raised_at')->get();
    }
}
