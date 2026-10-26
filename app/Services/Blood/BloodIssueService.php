<?php

namespace App\Services\Blood;

use App\Contracts\Billing\ChargeEventPublisher;
use App\Models\Blood\BloodCrossmatch;
use App\Models\Blood\BloodDiscard;
use App\Models\Blood\BloodIssue;
use App\Models\Blood\BloodRequest;
use App\Models\Blood\BloodReservation;
use App\Models\Blood\BloodSample;
use App\Models\Blood\BloodUnit;
use App\Models\Patient;
use App\Models\User;
use App\Services\Billing\Events\ChargeEventData;
use App\Services\Numbering\NumberSeriesService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Handing a reserved unit to the ward, taking it back, and throwing it away. A unit can leave the bank once at a time; an expired one never. */
class BloodIssueService
{
    public function __construct(
        private readonly BloodPolicy $policy,
        private readonly BloodUnitService $units,
        private readonly NumberSeriesService $numbers,
        private readonly ChargeEventPublisher $charges,
        private readonly BloodIncidentService $incidents,
        private readonly BloodCompatibilityService $compatibility,
    ) {}

    public function issue(BloodRequest $request, BloodUnit $unit, User $by, ?string $issuedTo = null, ?float $tempC = null): BloodIssue
    {
        return DB::transaction(function () use ($request, $unit, $by, $issuedTo, $tempC) {
            $lockedUnit = BloodUnit::query()->lockForUpdate()->findOrFail($unit->id);
            $lockedRequest = BloodRequest::query()->lockForUpdate()->findOrFail($request->id);
            $reservation = BloodReservation::query()->where('request_id', $lockedRequest->id)->where('unit_id', $lockedUnit->id)->where('status', 'active')->lockForUpdate()->first();
            if (! $reservation || $lockedUnit->status !== BloodUnit::RESERVED) {
                throw ValidationException::withMessages(['unit' => 'This unit is not reserved for this request.']);
            }
            if ($reservation->reserved_until->lte(now())) {
                throw ValidationException::withMessages(['unit' => 'The reservation has lapsed. Cross-match again.']);
            }
            if ($lockedUnit->blocked_reason) {
                throw ValidationException::withMessages(['unit' => 'The unit is blocked.']);
            }

            $this->units->move($lockedUnit, BloodUnit::ISSUED, $by, "Issued for {$lockedRequest->request_number}");
            $issue = $this->record($lockedRequest, $lockedUnit, $by, ['issued_to' => $issuedTo, 'issue_temp_c' => $tempC]);
            $reservation->update(['status' => 'issued', 'released_at' => now()]);

            return $issue;
        });
    }

    /**
     * Emergency release with no cross-match: only red cells of the emergency group (default O negative) or plasma of the universal group, only
     * with a named authoriser and a reason; it opens an incident and a cross-match is owed afterwards.
     */
    public function emergencyIssue(BloodRequest $request, BloodUnit $unit, User $by, string $authorisedBy, string $reason, ?string $issuedTo = null): BloodIssue
    {
        if (strlen(trim($reason)) < 5 || strlen(trim($authorisedBy)) < 2) {
            throw ValidationException::withMessages(['reason' => 'An emergency release needs the doctor who authorised it and a reason.']);
        }

        return DB::transaction(function () use ($request, $unit, $by, $authorisedBy, $reason, $issuedTo) {
            $lockedUnit = BloodUnit::query()->lockForUpdate()->findOrFail($unit->id);
            $lockedRequest = BloodRequest::query()->lockForUpdate()->findOrFail($request->id);
            if (! $lockedRequest->isOpen() || $lockedUnit->status !== BloodUnit::AVAILABLE) {
                throw ValidationException::withMessages(['unit' => 'The request is closed or the unit is not on the shelf.']);
            }
            $universal = $this->policy->get('emergency_release_group');
            $ok = match ($lockedUnit->kind()) {
                'red' => $lockedUnit->abo === $universal['abo'] && $lockedUnit->rh === $universal['rh'],
                'plasma' => $lockedUnit->abo === 'AB',
                default => true,
            };
            if (! $ok || $lockedUnit->component !== $lockedRequest->component) {
                throw ValidationException::withMessages(['unit' => 'Only the emergency group of the requested component can be released without cross-matching.']);
            }

            $this->units->move($lockedUnit, BloodUnit::ISSUED, $by, "EMERGENCY release for {$lockedRequest->request_number}, authorised by $authorisedBy");
            $issue = $this->record($lockedRequest, $lockedUnit, $by, ['issued_to' => $issuedTo, 'is_emergency' => true, 'crossmatched' => false, 'emergency_reason' => $reason, 'authorised_by' => $authorisedBy]);
            $this->incidents->open($lockedRequest->organization_id, 'emergency_release', "Uncross-matched unit {$lockedUnit->unit_number} released to {$lockedRequest->request_number}: $reason (authorised by $authorisedBy).", 'high', Patient::find($lockedRequest->patient_id), $lockedUnit, $issue, $by);

            return $issue;
        });
    }

    /** After an emergency release the cross-match is done on the first accepted sample; an incompatible result is a serious incident. */
    public function retrospectiveCrossmatch(BloodIssue $issue, User $by): BloodCrossmatch
    {
        $request = BloodRequest::findOrFail($issue->request_id);
        $unit = BloodUnit::findOrFail($issue->unit_id);
        $sample = BloodSample::query()->where('patient_id', $issue->patient_id)->where('status', 'accepted')->latest('id')->first()
            ?? throw ValidationException::withMessages(['sample' => 'No accepted sample yet — the patient group is still unconfirmed.']);
        $reason = $this->compatibility->incompatibility($request, $unit, ['abo' => $sample->abo, 'rh' => $sample->rh]);
        $match = BloodCrossmatch::create([
            'organization_id' => $issue->organization_id, 'request_id' => $request->id, 'unit_id' => $unit->id, 'sample_id' => $sample->id, 'method' => 'retrospective',
            'result' => $reason ? 'incompatible' : 'compatible', 'reason' => $reason, 'performed_at' => now(), 'performed_by' => $by->id,
        ]);
        $issue->update(['crossmatched' => true]);
        $this->units->event($unit, 'retrospective_crossmatch', $by, $reason ?? 'Compatible', $match);
        if ($reason) {
            $this->incidents->open($issue->organization_id, 'transfusion_reaction', "Retrospective cross-match INCOMPATIBLE for {$unit->unit_number}: $reason", 'critical', Patient::find($issue->patient_id), $unit, $issue, $by);
        }

        return $match;
    }

    /** Takes a unit back. It goes back on the shelf only if it came back in time and cold enough; otherwise it is discarded. */
    public function returnUnit(BloodIssue $issue, User $by, int $minutesOut, float $tempC, ?string $reason = null): BloodIssue
    {
        return DB::transaction(function () use ($issue, $by, $minutesOut, $tempC, $reason) {
            $locked = BloodIssue::query()->lockForUpdate()->findOrFail($issue->id);
            if ($locked->status !== 'issued') {
                throw ValidationException::withMessages(['issue' => 'Only a unit that has been issued and not yet started can be returned.']);
            }
            $rule = $this->policy->get('return');
            $accepted = $minutesOut <= (int) $rule['max_minutes_out'] && $tempC >= $rule['temp'][0] && $tempC <= $rule['temp'][1];
            $unit = BloodUnit::query()->lockForUpdate()->findOrFail($locked->unit_id);

            $locked->update([
                'status' => $accepted ? 'returned' : 'discarded', 'returned_at' => now(), 'minutes_out' => $minutesOut, 'return_temp_c' => $tempC, 'return_reason' => $reason, 'return_accepted' => $accepted,
            ]);
            if ($accepted && ! $unit->isExpired()) {
                $this->units->move($unit, BloodUnit::AVAILABLE, $by, "Returned in {$minutesOut} min at {$tempC} °C");
            } else {
                $this->discard($unit, 'returned_unfit', $by, $accepted ? 'Expired while out' : "Out {$minutesOut} min at {$tempC} °C — outside the return rule");
            }
            $this->charges->reverse($locked->organization_id, $this->chargeKey($locked, $unit), 'Unit returned', $by->id);
            BloodRequest::query()->whereKey($locked->request_id)->update(['status' => 'sample_received']);

            return $locked->refresh();
        });
    }

    public function discard(BloodUnit $unit, string $reason, User $by, ?string $note = null): BloodDiscard
    {
        if (! in_array($reason, $this->policy->get('discard_reasons'), true)) {
            throw ValidationException::withMessages(['reason' => 'Not a known discard reason.']);
        }

        return DB::transaction(function () use ($unit, $reason, $by, $note) {
            $locked = $this->units->move($unit, BloodUnit::DISCARDED, $by, "Discarded: $reason".($note ? " — $note" : ''));
            DB::table('blood_reservations')->where('unit_id', $locked->id)->where('status', 'active')->update(['status' => 'released', 'released_at' => now(), 'updated_at' => now()]);

            return BloodDiscard::create(['organization_id' => $locked->organization_id, 'unit_id' => $locked->id, 'component' => $locked->component, 'reason' => $reason, 'note' => $note, 'discarded_at' => now(), 'discarded_by' => $by->id]);
        });
    }

    public function releaseReservation(BloodReservation $reservation, string $note): void
    {
        DB::transaction(function () use ($reservation, $note) {
            $locked = BloodReservation::query()->lockForUpdate()->findOrFail($reservation->id);
            if ($locked->status !== 'active') {
                return;
            }
            $locked->update(['status' => 'released', 'released_at' => now()]);
            $unit = BloodUnit::query()->find($locked->unit_id);
            if ($unit && $unit->status === BloodUnit::RESERVED) {
                $this->units->move($unit, BloodUnit::AVAILABLE, null, $note);
            }
        });
    }

    /** Reservations past their time free their units. @return int released */
    public function releaseLapsedReservations(): int
    {
        $count = 0;
        BloodReservation::query()->where('status', 'active')->where('reserved_until', '<=', now())->orderBy('id')->each(function (BloodReservation $reservation) use (&$count) {
            DB::transaction(function () use ($reservation) {
                $locked = BloodReservation::query()->lockForUpdate()->find($reservation->id);
                if (! $locked || $locked->status !== 'active') {
                    return;
                }
                $locked->update(['status' => 'expired', 'released_at' => now()]);
                $unit = BloodUnit::query()->find($locked->unit_id);
                if ($unit && $unit->status === BloodUnit::RESERVED) {
                    $this->units->move($unit, BloodUnit::AVAILABLE, null, 'Reservation lapsed');
                }
            });
            $count++;
        });

        return $count;
    }

    /** @param  array<string, mixed>  $extra */
    private function record(BloodRequest $request, BloodUnit $unit, User $by, array $extra): BloodIssue
    {
        $issue = BloodIssue::create($extra + [
            'organization_id' => $request->organization_id, 'request_id' => $request->id, 'unit_id' => $unit->id, 'patient_id' => $request->patient_id, 'status' => 'issued',
            'issue_number' => $this->numbers->next('blood.issue', $request->organization_id, null, prefix: 'ISS', format: '{PREFIX}-{YEAR}-{SEQ:8}', userId: $by->id),
            'issued_at' => now(), 'issued_by' => $by->id,
        ]);
        $issued = BloodIssue::query()->where('request_id', $request->id)->whereIn('status', ['issued', 'transfusing', 'transfused'])->count();
        $request->update(['status' => $issued >= $request->units_requested ? 'issued' : 'partially_issued']);

        $this->charges->publish(new ChargeEventData(
            organizationId: $request->organization_id, branchId: (int) ($request->branch_id ?? 0), patientId: $request->patient_id, module: 'BLD', serviceCode: $this->policy->component($unit->component)['charge'],
            sourceEventId: $issue->id, visitRef: $request->request_number, encounterId: $request->encounter_id, performedAt: now(), performedBy: $by->id, sourceType: BloodIssue::class, sourceId: $issue->id,
        ));

        return $issue;
    }

    private function chargeKey(BloodIssue $issue, BloodUnit $unit): string
    {
        $request = BloodRequest::findOrFail($issue->request_id);

        return ChargeEventData::key('BLD', $request->request_number, $this->policy->component($unit->component)['charge'], $issue->id);
    }
}
