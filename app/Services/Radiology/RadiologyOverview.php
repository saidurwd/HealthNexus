<?php

namespace App\Services\Radiology;

use App\Support\Dashboard\DashboardPeriod;
use App\Support\Dashboard\Days;
use App\Support\Dashboard\Spread;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * The figures of the radiology dashboard. Now: patients waiting for their scan, scans in progress, studies done but not yet reported (and how long the oldest has waited, and how many are urgent), critical findings nobody has
 * acknowledged. In the period (and the period before): studies completed, how long from the scan being finished to the radiologist's approved report (median and 90th percentile), studies by modality and per day.
 */
class RadiologyOverview
{
    /** @return array<string,mixed> */
    public function summary(int $organizationId, ?int $branchId, DashboardPeriod $period, ?CarbonImmutable $now = null): array
    {
        $now = $now ?? CarbonImmutable::now();
        $exams = fn () => DB::table('radiology_examinations as e')->where('e.organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('e.branch_id', $branchId));
        $unreported = fn () => $exams()->join('radiology_order_items as i', 'i.id', '=', 'e.order_item_id')->whereIn('e.status', ['completed', 'images_available'])->whereNotIn('i.status', ['reported', 'cancelled']);
        $in = function (DashboardPeriod $p) use ($exams, $organizationId, $branchId) {
            $tat = DB::table('radiology_reports as r')->join('radiology_examinations as e', 'e.id', '=', 'r.examination_id')->where('r.organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('r.branch_id', $branchId))
                ->where('r.status', 'final')->where('r.is_current', true)->whereNotNull('e.completed_at')->whereBetween('r.approved_at', [$p->fromUtc(), $p->toUtc()])->get(['e.completed_at', 'r.approved_at'])->map(fn ($x) => Spread::minutesBetween($x->completed_at, $x->approved_at));

            return ['completed' => (int) $exams()->whereBetween('e.completed_at', [$p->fromUtc(), $p->toUtc()])->count(), 'tat' => Spread::of($tat)];
        };
        $oldest = $unreported()->min('e.completed_at');
        $modalities = $exams()->join('radiology_modalities as m', 'm.id', '=', 'e.modality_id')->whereBetween('e.completed_at', [$period->fromUtc(), $period->toUtc()])->selectRaw('m.name as modality, count(*) as n')->groupBy('m.id', 'm.name')->orderByDesc('n')->get();

        return [
            'waiting_patients' => (int) $exams()->whereIn('e.status', ['checked_in', 'preparing', 'ready'])->count(),
            'in_progress' => (int) $exams()->where('e.status', 'in_progress')->count(),
            'awaiting_reporting' => (int) $unreported()->count(), 'urgent_waiting' => (int) $unreported()->whereIn('i.priority', ['urgent', 'stat'])->count(),
            'oldest_waiting_minutes' => $oldest ? Spread::minutesBetween($oldest, $now) : null,
            'critical_unacknowledged' => (int) DB::table('radiology_critical_findings')->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->whereNull('acknowledged_at')->count(),
            'reports_pending_approval' => (int) DB::table('radiology_reports')->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->where('status', 'submitted')->count(),
            'current' => $in($period), 'previous' => $in($period->previous()),
            'by_modality' => $modalities->map(fn ($r) => ['modality' => $r->modality, 'studies' => (int) $r->n])->all(),
            'per_day' => collect(Days::perDay($exams(), 'e.completed_at', $period->from, $period->to))->map(fn ($n, $d) => ['day' => CarbonImmutable::parse($d)->format('d M'), 'studies' => (int) $n])->values()->all(),
        ];
    }
}
