<?php

namespace App\Services\Dashboard;

use App\Support\Dashboard\Days;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * The reception queue board: how long people are waiting and which doctors are busy, for one hospital (and branch) and today's Dhaka date. It shows token numbers, never names, so it can be put on a wall.
 */
class QueueBoardOverview
{
    /** @return array<string,mixed> */
    public function summary(int $organizationId, ?int $branchId, ?CarbonImmutable $now = null): array
    {
        $now = $now ?? CarbonImmutable::now();
        $date = Days::today($now)->toDateString();
        $tokens = fn () => DB::table('appointment_tokens as t')->join('appointments as a', 'a.id', '=', 't.appointment_id')->where('a.organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('a.branch_id', $branchId))
            ->whereDate('a.appointment_date', $date)->whereNull('t.deleted_at');
        $waiting = (clone $tokens())->where('t.status', 'waiting')->get(['t.id', 't.token_number', 't.generated_at', 't.priority', 'a.provider_id']);
        $minutes = fn ($row) => $row->generated_at ? max(0, (int) CarbonImmutable::parse($row->generated_at, 'UTC')->diffInMinutes($now)) : 0;
        $waits = $waiting->map($minutes);
        $served = (clone $tokens())->whereNotNull('t.called_at')->whereNotNull('t.generated_at')->get(['t.generated_at', 't.called_at']);
        $servedWaits = $served->map(fn ($r) => max(0, (int) CarbonImmutable::parse($r->generated_at, 'UTC')->diffInMinutes(CarbonImmutable::parse($r->called_at, 'UTC'))));
        $providers = DB::table('providers')->where('organization_id', $organizationId)->pluck('name', 'id');
        $byDoctor = (clone $tokens())->selectRaw('a.provider_id as provider_id, t.status as status, count(*) as n')->groupBy('a.provider_id', 't.status')->get()->groupBy('provider_id')->map(function ($rows, $pid) use ($providers, $waiting, $minutes) {
            $by = $rows->pluck('n', 'status');
            $mine = $waiting->where('provider_id', $pid);

            return ['doctor' => $providers[$pid] ?? __('Not assigned'), 'waiting' => (int) ($by['waiting'] ?? 0), 'in_consultation' => (int) (($by['called'] ?? 0) + ($by['in_progress'] ?? 0)), 'done' => (int) ($by['completed'] ?? 0),
                'longest' => $mine->isEmpty() ? null : (int) $mine->map($minutes)->max(), 'next' => $mine->sortBy('generated_at')->first()?->token_number];
        })->sortByDesc('waiting')->values()->all();

        return [
            'date' => $date, 'waiting' => $waiting->count(), 'longest' => $waits->isEmpty() ? null : (int) $waits->max(), 'average_served' => $servedWaits->isEmpty() ? null : round($servedWaits->avg(), 1), 'served_count' => $servedWaits->count(),
            'seen' => (int) (clone $tokens())->where('t.status', 'completed')->count(), 'no_shows' => (int) DB::table('appointments')->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->whereDate('appointment_date', $date)->where('status', 'no_show')->count(),
            'doctors_busy' => collect($byDoctor)->filter(fn ($d) => $d['waiting'] + $d['in_consultation'] + $d['done'] > 0)->count(), 'by_doctor' => $byDoctor,
            'waiting_by_hour' => collect($waiting)->groupBy(fn ($r) => CarbonImmutable::parse($r->generated_at ?? $now, 'UTC')->setTimezone(Days::timezone())->format('H:00'))->map->count()->sortKeys()->map(fn ($n, $h) => ['hour' => $h, 'n' => $n])->values()->all(),
        ];
    }
}
