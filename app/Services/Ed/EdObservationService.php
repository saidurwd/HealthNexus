<?php

namespace App\Services\Ed;

use App\Contracts\Billing\ChargeEventPublisher;
use App\Models\Ed\EdObservationStay;
use App\Models\Ed\EdVisit;
use App\Models\User;
use App\Services\Billing\Events\ChargeEventData;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** The short-stay observation unit. Ending a stay bills it by the hour, once (the charge event is idempotent on the stay). */
class EdObservationService
{
    public function __construct(private readonly EdVisitService $visits, private readonly ChargeEventPublisher $charges) {}

    public function start(EdVisit $visit, User $by): EdObservationStay
    {
        return DB::transaction(function () use ($visit, $by) {
            $locked = EdVisit::query()->lockForUpdate()->findOrFail($visit->id);
            if (! $locked->isActive()) {
                throw ValidationException::withMessages(['visit' => 'This visit is closed.']);
            }
            if (EdObservationStay::query()->where('visit_id', $locked->id)->whereNull('ended_at')->exists()) {
                throw ValidationException::withMessages(['visit' => 'The patient is already in the observation unit.']);
            }

            $stay = EdObservationStay::create(['organization_id' => $locked->organization_id, 'visit_id' => $locked->id, 'started_at' => now(), 'started_by' => $by->id]);

            $from = $locked->status;
            $locked->update(['status' => EdVisit::OBSERVATION]);
            $this->visits->recordStatus($locked, $from, EdVisit::OBSERVATION, $by, 'Moved to observation');

            return $stay;
        });
    }

    public function end(EdObservationStay $stay, User $by): EdObservationStay
    {
        return DB::transaction(function () use ($stay, $by) {
            $locked = EdObservationStay::query()->lockForUpdate()->findOrFail($stay->id);
            if ($locked->ended_at !== null) {
                throw ValidationException::withMessages(['stay' => 'The observation stay has already ended.']);
            }

            $ended = now();
            $hours = $this->billedHours($locked->started_at->diffInMinutes($ended, true));
            $locked->update(['ended_at' => $ended, 'ended_by' => $by->id, 'billed_hours' => $hours]);

            $visit = EdVisit::query()->lockForUpdate()->findOrFail($locked->visit_id);
            if ($visit->isActive()) {
                $from = $visit->status;
                $visit->update(['status' => EdVisit::AWAITING_DISPOSITION]);
                $this->visits->recordStatus($visit, $from, EdVisit::AWAITING_DISPOSITION, $by, 'Observation ended');
            }

            $this->charges->publish(new ChargeEventData(
                organizationId: $visit->organization_id, branchId: $visit->branch_id, patientId: $visit->patient_id, module: 'ED',
                serviceCode: config('ed.observation.service_code'), sourceEventId: $locked->id, visitRef: $visit->visit_number,
                encounterId: $visit->encounter_id, quantity: $hours, performedAt: $ended, performedBy: $by->id, sourceType: EdObservationStay::class, sourceId: $locked->id,
            ));

            return $locked;
        });
    }

    /** max(minimum, ceil((minutes − grace) / 60)): a stay inside the grace period still bills the minimum. */
    public function billedHours(int|float $minutes): int
    {
        $rule = config('ed.observation');

        return max((int) $rule['minimum_hours'], (int) ceil(max(0, $minutes - (int) $rule['grace_minutes']) / 60));
    }
}
