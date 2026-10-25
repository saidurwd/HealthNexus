<?php

namespace App\Services\Ed;

use App\Models\Ed\EdVisit;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/** The department's headline numbers over a period. Computed in PHP from the visit rows so it behaves the same on every database. */
class EdKpiService
{
    /**
     * @return array{arrivals: int, active_now: int, door_to_triage: ?array{median: int, p90: int}, door_to_doctor: ?array{median: int, p90: int}, length_of_stay: ?array{median: int, p90: int}, lwbs_percent: float, door_to_doctor_met_percent: ?float, by_level: array<int|string, int>, by_disposition: array<string, int>}
     */
    public function summary(int $organizationId, ?int $branchId, CarbonInterface $from, CarbonInterface $to): array
    {
        /** @var Collection<int, EdVisit> $visits */
        $visits = EdVisit::query()->forTenant($organizationId, $branchId)->whereBetween('arrival_at', [$from, $to])->get();

        $closed = $visits->whereNotNull('dispositioned_at');
        $withDoctor = $visits->whereNotNull('doctor_seen_at')->whereNotNull('doctor_due_at');

        return [
            'arrivals' => $visits->count(),
            'active_now' => EdVisit::query()->forTenant($organizationId, $branchId)->active()->count(),
            'door_to_triage' => $this->spread($visits->whereNotNull('triaged_at')->map(fn (EdVisit $v) => $v->arrival_at->diffInMinutes($v->triaged_at, true))),
            'door_to_doctor' => $this->spread($visits->whereNotNull('doctor_seen_at')->map(fn (EdVisit $v) => $v->arrival_at->diffInMinutes($v->doctor_seen_at, true))),
            'length_of_stay' => $this->spread($closed->map(fn (EdVisit $v) => $v->arrival_at->diffInMinutes($v->dispositioned_at, true))),
            'lwbs_percent' => $closed->isEmpty() ? 0.0 : round(100 * $closed->where('disposition_type', 'lwbs')->count() / $closed->count(), 1),
            'door_to_doctor_met_percent' => $withDoctor->isEmpty() ? null : round(100 * $withDoctor->filter(fn (EdVisit $v) => $v->doctor_seen_at->lte($v->doctor_due_at))->count() / $withDoctor->count(), 1),
            'by_level' => $visits->groupBy(fn (EdVisit $v) => $v->triage_level ?? 'untriaged')->map->count()->sortKeys()->all(),
            'by_disposition' => $closed->groupBy('disposition_type')->map->count()->sortKeys()->all(),
        ];
    }

    /** @param Collection<int, mixed> $minutes @return array{median: int, p90: int}|null */
    private function spread(Collection $minutes): ?array
    {
        $sorted = $minutes->map(fn ($m) => (int) round((float) $m))->sort()->values();
        if ($sorted->isEmpty()) {
            return null;
        }

        return ['median' => $this->percentile($sorted, 50), 'p90' => $this->percentile($sorted, 90)];
    }

    /** @param Collection<int, int> $sorted nearest-rank percentile */
    private function percentile(Collection $sorted, int $p): int
    {
        return $sorted[max(0, (int) ceil($p / 100 * $sorted->count()) - 1)];
    }
}
