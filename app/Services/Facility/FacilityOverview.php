<?php

namespace App\Services\Facility;

use App\Models\Facility\FacAmbulance;
use App\Models\Facility\FacAmbulanceTrip;
use App\Models\Facility\FacAsset;
use App\Models\Facility\FacCleaningTask;
use App\Models\Facility\FacDietOrder;
use App\Models\Facility\FacGateLog;
use App\Models\Facility\FacLinenType;
use App\Models\Facility\FacMaintenancePlan;
use App\Models\Facility\FacMortuaryCase;
use App\Models\Facility\FacSterilisationBatch;
use App\Models\Facility\FacVisitorPass;
use App\Models\Facility\FacWorkOrder;

/** The figures on the facility home page. */
class FacilityOverview
{
    public function __construct(private readonly LinenService $linen, private readonly WasteService $waste, private readonly MortuaryService $mortuary, private readonly VisitorService $visitors, private readonly AssetService $assets, private readonly FacilityPolicy $policy) {}

    /** @return array<string,mixed> */
    public function summary(int $organizationId, int $days = 30): array
    {
        $from = today()->subDays($days - 1);
        $assets = FacAsset::query()->where('organization_id', $organizationId);
        $wo = FacWorkOrder::query()->where('organization_id', $organizationId);
        $belowPar = FacLinenType::query()->where('organization_id', $organizationId)->where('active', true)->get()->filter(fn ($t) => $this->linen->balance($t)['clean_store'] < $t->par_level)->count();
        $inside = FacVisitorPass::query()->where('organization_id', $organizationId)->where('status', 'active')->get()->filter(fn ($p) => $this->visitors->inside($p))->count();
        $turn = FacCleaningTask::query()->where('organization_id', $organizationId)->whereNotNull('turnaround_minutes')->where('done_at', '>=', $from)->avg('turnaround_minutes');

        return [
            'assets' => (clone $assets)->where('status', '!=', 'retired')->count(),
            'assets_down' => (clone $assets)->whereIn('status', ['out_of_service', 'under_maintenance'])->count(),
            'high_risk_blocked' => (clone $assets)->where('risk_class', 'high')->where('status', '!=', 'retired')->get()->filter(fn ($a) => ! $this->assets->usable($a))->count(),
            'calibrations_overdue' => FacMaintenancePlan::query()->where('organization_id', $organizationId)->where('active', true)->where('kind', 'calibration')->where('next_due_on', '<', today()->toDateString())->count(),
            'work_orders_open' => (clone $wo)->whereIn('status', ['open', 'in_progress', 'waiting_parts'])->count(),
            'work_orders_urgent' => (clone $wo)->whereIn('status', ['open', 'in_progress', 'waiting_parts'])->where('priority', 'urgent')->count(),
            'work_orders_overdue' => (clone $wo)->whereIn('status', ['open', 'in_progress', 'waiting_parts'])->whereNotNull('due_on')->where('due_on', '<', today()->toDateString())->count(),
            'work_orders_to_verify' => (clone $wo)->where('status', 'done')->whereHas('asset', fn ($q) => $q->where('risk_class', 'high'))->whereIn('kind', ['breakdown', 'calibration'])->count(),
            'downtime_hours' => round(((int) (clone $wo)->where('kind', 'breakdown')->where('completed_at', '>=', $from)->sum('downtime_minutes')) / 60, 1),
            'cleaning_waiting' => FacCleaningTask::query()->where('organization_id', $organizationId)->whereIn('status', ['pending', 'in_progress', 'done'])->where('bed_released', false)->count(),
            'turnaround_minutes' => $turn === null ? null : round((float) $turn, 1),
            'turnaround_target' => $this->policy->int('housekeeping.turnover_target_minutes', 45),
            'batches_to_release' => FacSterilisationBatch::query()->where('organization_id', $organizationId)->where('status', 'passed')->count(),
            'linen_below_par' => $belowPar,
            'waste_kg' => array_sum($this->waste->totals($organizationId, $from->toDateString(), today()->toDateString())),
            'diets_active' => FacDietOrder::query()->where('organization_id', $organizationId)->where('status', 'active')->count(),
            'ambulances_available' => FacAmbulance::query()->where('organization_id', $organizationId)->where('status', 'available')->count(),
            'ambulances_on_trip' => FacAmbulance::query()->where('organization_id', $organizationId)->where('status', 'on_trip')->count(),
            'trips_waiting' => FacAmbulanceTrip::query()->where('organization_id', $organizationId)->where('status', 'requested')->count(),
            'mortuary' => $this->mortuary->occupancy($organizationId),
            'mortuary_waiting' => FacMortuaryCase::query()->where('organization_id', $organizationId)->where('status', 'pending')->count(),
            'passes_active' => FacVisitorPass::query()->where('organization_id', $organizationId)->where('status', 'active')->count(),
            'visitors_inside' => $inside,
            'gate_refusals' => FacGateLog::query()->where('organization_id', $organizationId)->where('outcome', 'refused')->where('created_at', '>=', $from)->count(),
        ];
    }
}
