<?php

namespace App\Services\Facility;

use App\Models\Facility\FacAsset;
use App\Models\Facility\FacCalibration;
use App\Models\Facility\FacMaintenancePlan;
use App\Models\Facility\FacWorkOrder;
use App\Models\Facility\FacWorkOrderPart;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Numbering\NumberSeriesService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Keeping equipment safe and working: plans that fall due and become work orders, breakdown tickets, calibration records and spare parts. A breakdown takes the equipment out of use until it is
 * fixed (downtime is measured); a failed calibration keeps it out of use until a passing one; repairs of high-risk equipment are checked by someone other than the engineer.
 */
class MaintenanceService
{
    public function __construct(private readonly NumberSeriesService $numbers, private readonly FacilityPolicy $policy, private readonly FacilityAlerts $alerts, private readonly AuditLogger $audit) {}

    public function plan(FacAsset $asset, string $kind, string $title, int $intervalDays, ?string $firstDue = null): FacMaintenancePlan
    {
        if (! in_array($kind, ['preventive', 'calibration', 'safety_test'], true) || mb_strlen(trim($title)) < 3 || $intervalDays < 1 || $intervalDays > 3650) {
            throw ValidationException::withMessages(['title' => __('Give the plan a kind, a title and a number of days.')]);
        }
        if ($asset->status === 'retired') {
            throw ValidationException::withMessages(['asset' => __('This equipment is retired.')]);
        }
        $plan = FacMaintenancePlan::create(['organization_id' => $asset->organization_id, 'asset_id' => $asset->id, 'kind' => $kind, 'title' => mb_substr(trim($title), 0, 150), 'interval_days' => $intervalDays,
            'next_due_on' => $firstDue ?: today()->addDays($intervalDays)->toDateString(), 'active' => true]);
        $this->audit->log('CREATE', FacMaintenancePlan::class, $plan->id, null, ['asset' => $asset->tag, 'kind' => $kind]);

        return $plan;
    }

    /** Open a work order for each plan due within the lead time. @return int orders opened */
    public function openDue(int $organizationId): int
    {
        $limit = today()->addDays($this->policy->int('maintenance.lead_days', 7))->toDateString();
        $n = 0;
        foreach (FacMaintenancePlan::query()->with('asset')->where('organization_id', $organizationId)->where('active', true)->where('next_due_on', '<=', $limit)->get() as $plan) {
            if (! $plan->asset || $plan->asset->status === 'retired') {
                continue;
            }
            if (FacWorkOrder::query()->where('plan_id', $plan->id)->whereIn('status', ['open', 'in_progress', 'waiting_parts', 'done'])->exists()) {
                continue;
            }
            $kind = $plan->kind === 'calibration' ? 'calibration' : 'preventive';
            $this->create($organizationId, ['asset_id' => $plan->asset_id, 'plan_id' => $plan->id, 'kind' => $kind, 'title' => $plan->title.' — '.$plan->asset->tag, 'priority' => $plan->next_due_on->lt(today()) ? 'urgent' : 'normal', 'due_on' => $plan->next_due_on->toDateString(),
                'branch_id' => $plan->asset->branch_id], null);
            $n++;
        }

        return $n;
    }

    /** @param array<string,mixed> $d asset_id, plan_id, kind, title, description, priority, due_on, branch_id */
    public function create(int $organizationId, array $d, ?User $by): FacWorkOrder
    {
        if (! in_array($d['kind'] ?? '', ['preventive', 'calibration', 'breakdown', 'inspection', 'other'], true) || mb_strlen(trim((string) ($d['title'] ?? ''))) < 3) {
            throw ValidationException::withMessages(['title' => __('Give the work order a kind and a title.')]);
        }
        if (! in_array($d['priority'] ?? 'normal', $this->policy->list('maintenance.priorities'), true)) {
            throw ValidationException::withMessages(['priority' => __('Choose a priority.')]);
        }
        $asset = ! empty($d['asset_id']) ? FacAsset::query()->where('organization_id', $organizationId)->find($d['asset_id']) : null;
        if (! empty($d['asset_id']) && ! $asset) {
            throw ValidationException::withMessages(['asset_id' => __('Unknown equipment.')]);
        }
        $wo = FacWorkOrder::create(['organization_id' => $organizationId, 'branch_id' => $d['branch_id'] ?? $asset?->branch_id, 'number' => $this->numbers->next('fac.wo', $organizationId, null, prefix: 'WO', format: '{PREFIX}-{YEAR}-{SEQ:5}', period: NumberSeriesService::PERIOD_YEAR),
            'asset_id' => $asset?->id, 'plan_id' => $d['plan_id'] ?? null, 'kind' => $d['kind'], 'title' => mb_substr(trim($d['title']), 0, 200), 'description' => isset($d['description']) ? mb_substr(strip_tags((string) $d['description']), 0, 4000) : null,
            'priority' => $d['priority'] ?? 'normal', 'status' => 'open', 'reported_by' => $by?->id, 'reported_at' => now(), 'due_on' => $d['due_on'] ?? null]);
        $this->audit->log('CREATE', FacWorkOrder::class, $wo->id, null, ['number' => $wo->number, 'kind' => $wo->kind]);

        return $wo;
    }

    /** A breakdown ticket: the equipment is taken out of use now. */
    public function reportBreakdown(FacAsset $asset, User $by, string $description, string $priority = 'urgent', bool $stopUse = true): FacWorkOrder
    {
        if ($asset->status === 'retired') {
            throw ValidationException::withMessages(['asset' => __('This equipment is retired.')]);
        }
        if (mb_strlen(trim($description)) < 5) {
            throw ValidationException::withMessages(['description' => __('Say what is wrong.')]);
        }

        return DB::transaction(function () use ($asset, $by, $description, $priority, $stopUse) {
            $wo = $this->create($asset->organization_id, ['asset_id' => $asset->id, 'kind' => 'breakdown', 'title' => __('Breakdown: :n', ['n' => $asset->name]), 'description' => $description, 'priority' => $priority, 'branch_id' => $asset->branch_id], $by);
            if ($stopUse && $asset->status === 'in_service') {
                $asset->update(['status' => $asset->risk_class === 'high' ? 'out_of_service' : 'under_maintenance']);
            }
            $this->alerts->raise($asset->organization_id, $asset->risk_class === 'high' ? 'critical' : 'warning', __(':a is down: :d', ['a' => $asset->tag, 'd' => mb_substr($description, 0, 120)]), FacWorkOrder::class, $wo->id, 'fac-down-'.$wo->id, 'facility.workorder.manage', $asset->risk_class === 'high', $asset->branch_id);

            return $wo;
        });
    }

    public function assign(FacWorkOrder $wo, User $engineer): FacWorkOrder
    {
        $this->mustBe($wo, ['open', 'in_progress', 'waiting_parts']);
        $wo->update(['assigned_to' => $engineer->id]);

        return $wo;
    }

    public function start(FacWorkOrder $wo, User $by): FacWorkOrder
    {
        $this->mustBe($wo, ['open', 'waiting_parts']);
        $wo->update(['status' => 'in_progress', 'started_at' => $wo->started_at ?? now(), 'assigned_to' => $wo->assigned_to ?? $by->id]);

        return $wo;
    }

    public function waitForParts(FacWorkOrder $wo, string $note): FacWorkOrder
    {
        $this->mustBe($wo, ['in_progress']);
        $wo->update(['status' => 'waiting_parts', 'completion_note' => mb_substr($note, 0, 1000)]);

        return $wo;
    }

    public function addPart(FacWorkOrder $wo, string $description, float $quantity, float $unitCost = 0, ?int $itemId = null): FacWorkOrderPart
    {
        $this->mustBe($wo, ['open', 'in_progress', 'waiting_parts']);
        if (mb_strlen(trim($description)) < 2 || $quantity <= 0 || $unitCost < 0) {
            throw ValidationException::withMessages(['description' => __('Say which part and how many.')]);
        }
        $part = FacWorkOrderPart::create(['work_order_id' => $wo->id, 'item_id' => $itemId, 'description' => mb_substr(trim($description), 0, 200), 'quantity' => $quantity, 'unit_cost' => $unitCost]);
        $wo->update(['cost' => (float) $wo->parts()->get()->sum(fn ($p) => (float) $p->quantity * (float) $p->unit_cost)]);

        return $part;
    }

    /** The engineer says what was done. A breakdown ends the downtime; a plan moves to its next date. */
    public function complete(FacWorkOrder $wo, User $by, string $note): FacWorkOrder
    {
        $this->mustBe($wo, ['open', 'in_progress', 'waiting_parts']);
        if (mb_strlen(trim($note)) < 5) {
            throw ValidationException::withMessages(['note' => __('Say what was done.')]);
        }
        $asset = $wo->asset;
        if ($wo->kind === 'calibration' && $asset && ! FacCalibration::query()->where('work_order_id', $wo->id)->exists()) {
            throw ValidationException::withMessages(['note' => __('Record the calibration result first.')]);
        }

        return DB::transaction(function () use ($wo, $by, $note) {
            $wo->update(['status' => 'done', 'completed_at' => now(), 'completion_note' => mb_substr(strip_tags($note), 0, 1000), 'assigned_to' => $wo->assigned_to ?? $by->id,
                'downtime_minutes' => $wo->kind === 'breakdown' ? (int) max(0, $wo->reported_at->diffInMinutes(now())) : $wo->downtime_minutes]);
            $needsCheck = $this->needsSecondPerson($wo);
            if (! $needsCheck) {
                $this->finish($wo);
            }
            $this->audit->log('UPDATE', FacWorkOrder::class, $wo->id, null, ['status' => $wo->status]);

            return $wo->refresh();
        });
    }

    /** Someone other than the engineer checks repairs of high-risk equipment and calibrations. */
    public function verify(FacWorkOrder $wo, User $by, ?string $note = null): FacWorkOrder
    {
        $this->mustBe($wo, ['done']);
        if (! $this->needsSecondPerson($wo)) {
            throw ValidationException::withMessages(['status' => __('This work order needs no check.')]);
        }
        if ($wo->assigned_to === $by->id) {
            throw ValidationException::withMessages(['status' => __('A second person must check the work.')]);
        }
        $wo->update(['status' => 'verified', 'verified_by' => $by->id, 'verified_at' => now()]);
        $this->finish($wo);
        $this->audit->log('UPDATE', FacWorkOrder::class, $wo->id, null, ['status' => 'verified']);

        return $wo->refresh();
    }

    public function cancel(FacWorkOrder $wo, User $by, string $reason): FacWorkOrder
    {
        $this->mustBe($wo, ['open', 'in_progress', 'waiting_parts']);
        if (mb_strlen(trim($reason)) < 5) {
            throw ValidationException::withMessages(['reason' => __('Say why it is cancelled.')]);
        }
        $wo->update(['status' => 'cancelled', 'completion_note' => mb_substr($reason, 0, 1000)]);
        $this->audit->log('UPDATE', FacWorkOrder::class, $wo->id, null, ['status' => 'cancelled']);

        return $wo;
    }

    /** Record a calibration. A pass sets the next date and (if only the calibration kept it out) returns the equipment; a fail takes it out and opens an urgent order. */
    public function calibrate(FacAsset $asset, User $by, string $result, string $performedOn, ?string $nextDue = null, ?string $certificate = null, ?string $performedBy = null, ?FacWorkOrder $wo = null, ?string $note = null): FacCalibration
    {
        if (! in_array($result, ['pass', 'fail', 'adjusted'], true)) {
            throw ValidationException::withMessages(['result' => __('Choose a result.')]);
        }
        if (strtotime($performedOn) > strtotime(today()->toDateString())) {
            throw ValidationException::withMessages(['performed_on' => __('The date cannot be in the future.')]);
        }
        $plan = FacMaintenancePlan::query()->where(['asset_id' => $asset->id, 'kind' => 'calibration', 'active' => true])->first();
        $next = $nextDue ?: ($plan ? date('Y-m-d', strtotime("$performedOn +{$plan->interval_days} days")) : null);
        if ($result !== 'fail' && $next === null) {
            throw ValidationException::withMessages(['next_due_on' => __('Give the next calibration date.')]);
        }

        return DB::transaction(function () use ($asset, $by, $result, $performedOn, $next, $certificate, $performedBy, $wo, $note, $plan) {
            $cal = FacCalibration::create(['organization_id' => $asset->organization_id, 'asset_id' => $asset->id, 'work_order_id' => $wo?->id, 'performed_on' => $performedOn, 'next_due_on' => $result === 'fail' ? null : $next, 'result' => $result,
                'certificate_no' => $certificate ? mb_substr($certificate, 0, 60) : null, 'performed_by' => $performedBy ? mb_substr($performedBy, 0, 120) : null, 'note' => $note ? mb_substr($note, 0, 500) : null, 'recorded_by' => $by->id]);
            if ($result === 'fail') {
                $asset->update(['status' => 'out_of_service']);
                $open = FacWorkOrder::query()->where(['asset_id' => $asset->id, 'kind' => 'breakdown'])->whereIn('status', ['open', 'in_progress', 'waiting_parts'])->exists();
                $open || $this->create($asset->organization_id, ['asset_id' => $asset->id, 'kind' => 'breakdown', 'title' => __('Failed calibration: :n', ['n' => $asset->name]), 'description' => $note ?: __('The equipment failed its calibration and must not be used.'), 'priority' => 'urgent', 'branch_id' => $asset->branch_id], $by);
                $this->alerts->raise($asset->organization_id, 'critical', __(':a failed its calibration and is out of service.', ['a' => $asset->tag]), FacCalibration::class, $cal->id, 'fac-cal-'.$cal->id, 'facility.workorder.manage', true, $asset->branch_id);
            } elseif ($plan) {
                $plan->update(['last_done_on' => $performedOn, 'next_due_on' => $next]);
                $pending = $asset->risk_class === 'high' ? ['open', 'in_progress', 'waiting_parts', 'done'] : ['open', 'in_progress', 'waiting_parts']; // a high-risk repair waits for its check
                if ($asset->status === 'out_of_service' && ! FacWorkOrder::query()->where(['asset_id' => $asset->id, 'kind' => 'breakdown'])->whereIn('status', $pending)->exists()) {
                    $asset->update(['status' => 'in_service']);
                }
            }
            $this->audit->log('CREATE', FacCalibration::class, $cal->id, null, ['asset' => $asset->tag, 'result' => $result]);

            return $cal;
        });
    }

    /** Warn about calibrations that are overdue and warranties that end soon. @return array{calibrations:int,warranties:int} */
    public function alertDue(int $organizationId): array
    {
        $cal = 0;
        foreach (FacMaintenancePlan::query()->with('asset')->where('organization_id', $organizationId)->where('active', true)->where('kind', 'calibration')->where('next_due_on', '<', today()->toDateString())->get() as $plan) {
            if ($plan->asset && $plan->asset->risk_class === 'high' && $plan->asset->status !== 'retired') {
                $this->alerts->raise($organizationId, 'warning', __(':a: calibration is overdue since :d.', ['a' => $plan->asset->tag, 'd' => $plan->next_due_on->toDateString()]), FacMaintenancePlan::class, $plan->id, 'fac-cal-late-'.$plan->id.'-'.today()->toDateString(), 'facility.workorder.manage', false, $plan->asset->branch_id);
                $cal++;
            }
        }
        $warranty = FacAsset::query()->where('organization_id', $organizationId)->where('status', '!=', 'retired')->whereBetween('warranty_until', [today()->toDateString(), today()->addDays($this->policy->int('asset.warranty_warning_days', 60))->toDateString()])->count();
        $warranty > 0 && $this->alerts->raise($organizationId, 'info', __(':n warranty(ies) end soon.', ['n' => $warranty]), FacAsset::class, 0, 'fac-warranty-'.$organizationId.'-'.today()->toDateString(), 'facility.asset.manage');

        return ['calibrations' => $cal, 'warranties' => $warranty];
    }

    private function needsSecondPerson(FacWorkOrder $wo): bool
    {
        return $this->policy->bool('maintenance.second_person_verifies', true) && $wo->asset !== null && $wo->asset->risk_class === 'high' && in_array($wo->kind, ['breakdown', 'calibration'], true);
    }

    /** Work is accepted: the plan moves on and the equipment returns to service. */
    private function finish(FacWorkOrder $wo): void
    {
        if ($wo->plan_id) {
            $plan = FacMaintenancePlan::query()->find($wo->plan_id);
            $plan && $plan->update(['last_done_on' => today()->toDateString(), 'next_due_on' => today()->addDays($plan->interval_days)->toDateString()]);
        }
        $asset = $wo->asset;
        if ($asset && $wo->kind === 'breakdown' && in_array($asset->status, ['out_of_service', 'under_maintenance'], true)) {
            $failed = FacCalibration::query()->where('asset_id', $asset->id)->orderByDesc('performed_on')->orderByDesc('id')->value('result') === 'fail';
            $failed || $asset->update(['status' => 'in_service']);
        }
    }

    /** @param list<string> $states */
    private function mustBe(FacWorkOrder $wo, array $states): void
    {
        if (! in_array($wo->status, $states, true)) {
            throw ValidationException::withMessages(['status' => __('This work order is :s.', ['s' => str_replace('_', ' ', $wo->status)])]);
        }
    }
}
