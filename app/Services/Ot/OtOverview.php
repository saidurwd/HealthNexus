<?php

namespace App\Services\Ot;

use App\Models\Ot\OtSurgery;
use App\Models\Ot\OtSurgeryRequest;
use App\Models\Ot\OtTheatre;
use App\Support\Dashboard\Days;
use Carbon\CarbonImmutable;

/**
 * The figures of the OT / Surgery dashboard: today's list at a glance, theatre use, and the two things a theatre manager is judged on: how late cases start (average minutes after the planned start, last 30 days) and how many
 * planned cases are called off (cancelled or postponed, last 30 days). Days are Dhaka days.
 */
class OtOverview
{
    /** @return array<string,mixed> */
    public function summary(int $organizationId, ?int $branchId, ?CarbonImmutable $now = null): array
    {
        $today = Days::today($now);
        $day = $today->toDateString();
        $from30 = $today->subDays(29)->toDateString();
        $cases = fn () => OtSurgery::forTenant($organizationId, $branchId);
        $planned30 = (int) $cases()->whereBetween('scheduled_date', [$from30, $day])->count();
        $calledOff30 = (int) $cases()->whereBetween('scheduled_date', [$from30, $day])->whereIn('status', [OtSurgery::STATUS_CANCELLED, 'postponed'])->count();
        $delays = $cases()->whereBetween('scheduled_date', [$from30, $day])->whereNotNull('actual_start_at')->whereNotNull('scheduled_start_at')->get(['scheduled_start_at', 'actual_start_at'])
            ->map(fn ($s) => max(0, (int) CarbonImmutable::parse($s->scheduled_start_at)->diffInMinutes(CarbonImmutable::parse($s->actual_start_at), false)));

        return [
            'scheduled_today' => $cases()->whereDate('scheduled_date', $day)->whereNotIn('status', [OtSurgery::STATUS_CANCELLED, 'postponed'])->count(),
            'completed_today' => $cases()->whereDate('scheduled_date', $day)->where('status', OtSurgery::STATUS_PROCEDURE_COMPLETED)->count(),
            'in_procedure' => $cases()->where('status', OtSurgery::STATUS_IN_PROCEDURE)->count(),
            'in_recovery' => $cases()->where('status', OtSurgery::STATUS_RECOVERY)->count(),
            'cancelled_today' => $cases()->whereDate('scheduled_date', $day)->where('status', OtSurgery::STATUS_CANCELLED)->count(),
            'pending_requests' => OtSurgeryRequest::forTenant($organizationId, $branchId)->whereIn('status', [OtSurgeryRequest::STATUS_REQUESTED, OtSurgeryRequest::STATUS_PENDING_APPROVAL])->count(),
            'theatres_available' => OtTheatre::forTenant($organizationId, $branchId)->where('status', OtTheatre::STATUS_AVAILABLE)->count(),
            'theatres_occupied' => OtTheatre::forTenant($organizationId, $branchId)->where('status', OtTheatre::STATUS_OCCUPIED)->count(),
            'average_start_delay' => $delays->isEmpty() ? null : round($delays->avg(), 1), 'delay_cases' => $delays->count(),
            'called_off_rate' => $planned30 > 0 ? round(100 * $calledOff30 / $planned30, 1) : null, 'planned_30d' => $planned30, 'called_off_30d' => $calledOff30,
            'per_day' => collect(range(0, 13))->map(fn ($i) => ['day' => $today->subDays(13 - $i)->format('d M'), 'cases' => $cases()->whereDate('scheduled_date', $today->subDays(13 - $i)->toDateString())->count()])->all(),
        ];
    }
}
