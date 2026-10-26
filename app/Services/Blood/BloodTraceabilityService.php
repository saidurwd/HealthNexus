<?php

namespace App\Services\Blood;

use App\Models\Blood\BloodDonation;
use App\Models\Blood\BloodDonor;
use App\Models\Blood\BloodRecall;
use App\Models\Blood\BloodUnit;
use App\Models\Patient;
use App\Models\User;
use App\Services\Clinical\Alerts\AlertRequest;
use App\Services\Clinical\Alerts\AlertService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Both directions of the chain: from a donor to every recipient, and from a unit back to its donor — plus recall, which blocks what is left and lists who already received. */
class BloodTraceabilityService
{
    public function __construct(
        private readonly BloodUnitService $units,
        private readonly BloodIncidentService $incidents,
        private readonly BloodTestingService $testing,
        private readonly AlertService $alerts,
    ) {}

    /** @return Collection<int, array{patient_id: int, patient: string, mrn: ?string, unit_number: string, component: string, issued_at: string, state: string}> */
    public function recipientsOfDonor(BloodDonor $donor): Collection
    {
        return $this->recipients(fn ($q) => $q->where('d.donor_id', $donor->id));
    }

    /** @return Collection<int, array{patient_id: int, patient: string, mrn: ?string, unit_number: string, component: string, issued_at: string, state: string}> */
    public function recipientsOfDonation(BloodDonation $donation): Collection
    {
        return $this->recipients(fn ($q) => $q->where('u.donation_id', $donation->id));
    }

    /** Who received this bag (or the bags made from the same donation)? */
    public function recipientsOfUnit(BloodUnit $unit): Collection
    {
        return $this->recipients(fn ($q) => $q->where('u.id', $unit->id));
    }

    /** @return array{donor: ?BloodDonor, donation: ?BloodDonation} which donor gave this bag */
    public function donorOfUnit(BloodUnit $unit): array
    {
        $donation = $unit->donation_id ? BloodDonation::find($unit->donation_id) : null;

        return ['donation' => $donation, 'donor' => $donation ? BloodDonor::find($donation->donor_id) : null];
    }

    /** @return list<array<string, mixed>> the whole life of a bag, oldest first */
    public function chain(BloodUnit $unit): array
    {
        return $unit->events()->with([])->get()->map(fn ($e) => ['at' => $e->occurred_at->toDateTimeString(), 'event' => $e->event_type, 'from' => $e->from_status, 'to' => $e->to_status, 'note' => $e->note, 'by' => $e->actor_id])->all();
    }

    /**
     * Recall by donor (or one donation): every bag not yet transfused is blocked; everyone who already received a bag is listed and alerted.
     */
    public function recall(User $by, string $reason, ?BloodDonor $donor = null, ?BloodDonation $donation = null): BloodRecall
    {
        $donor ??= $donation ? BloodDonor::findOrFail($donation->donor_id) : null;
        $donationIds = $donation ? [$donation->id] : BloodDonation::query()->where('donor_id', $donor->id)->pluck('id')->all();

        return DB::transaction(function () use ($by, $reason, $donor, $donation, $donationIds) {
            $blocked = 0;
            foreach (BloodUnit::query()->whereIn('donation_id', $donationIds)->whereIn('status', [BloodUnit::QUARANTINED, BloodUnit::AVAILABLE, BloodUnit::RESERVED, BloodUnit::ISSUED])->get() as $unit) {
                DB::table('blood_reservations')->where('unit_id', $unit->id)->where('status', 'active')->update(['status' => 'released', 'released_at' => now(), 'updated_at' => now()]);
                $this->units->move($unit, BloodUnit::BLOCKED, $by, "Recalled: $reason", null, ['blocked_reason' => 'recalled']);
                $blocked++;
            }
            $recipients = $donation ? $this->recipientsOfDonation($donation) : $this->recipientsOfDonor($donor);
            $recall = BloodRecall::create([
                'organization_id' => ($donor ?? BloodDonor::findOrFail($donation->donor_id))->organization_id, 'donor_id' => $donor?->id, 'donation_id' => $donation?->id, 'reason' => $reason,
                'units_blocked' => $blocked, 'recipients_found' => $recipients->count(), 'recipient_patient_ids' => $recipients->pluck('patient_id')->unique()->values()->all(), 'initiated_at' => now(), 'initiated_by' => $by->id,
            ]);
            $this->incidents->open($recall->organization_id, 'recall', "Recall ($reason): $blocked bag(s) blocked, {$recipients->count()} recipient(s) to inform.", 'critical', null, null, $recall, $by, false);
            foreach ($recipients->where('state', 'transfused')->unique('patient_id') as $recipient) {
                $this->alerts->raise(new AlertRequest(
                    organizationId: $recall->organization_id, category: 'blood_recall_recipient', severity: 'critical', sourceModule: 'bloodbank', patientId: $recipient['patient_id'],
                    message: "Recall: {$recipient['patient']} received unit {$recipient['unit_number']} from a recalled donation. Review and inform the patient.", sourceType: BloodRecall::class, sourceId: $recall->id,
                    recipientUserIds: $this->testing->roleUsers($recall->organization_id), dedupKey: "blood-recall:{$recall->id}:{$recipient['patient_id']}", raisedBy: $by->id,
                ));
            }

            return $recall;
        });
    }

    /** @param  callable(Builder): mixed  $scope */
    private function recipients(callable $scope): Collection
    {
        $query = DB::table('blood_issues as i')->join('blood_units as u', 'u.id', '=', 'i.unit_id')->join('blood_donations as d', 'd.id', '=', 'u.donation_id')
            ->whereIn('i.status', ['issued', 'transfusing', 'transfused']);
        $scope($query);

        return $query->orderBy('i.issued_at')->get(['i.patient_id', 'i.status', 'i.issued_at', 'u.unit_number', 'u.component'])->map(function ($row) {
            $patient = Patient::find($row->patient_id);

            return ['patient_id' => (int) $row->patient_id, 'patient' => $patient ? $patient->full_name : '—', 'mrn' => $patient ? $patient->enterprise_patient_no : null, 'unit_number' => $row->unit_number,
                'component' => $row->component, 'issued_at' => (string) $row->issued_at, 'state' => $row->status === 'transfused' ? 'transfused' : 'issued'];
        });
    }
}
