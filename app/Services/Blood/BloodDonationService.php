<?php

namespace App\Services\Blood;

use App\Models\Blood\BloodDonation;
use App\Models\Blood\BloodDonor;
use App\Models\Blood\BloodDonorAdverseEvent;
use App\Models\Blood\BloodDonorScreening;
use App\Models\Blood\BloodStorageLocation;
use App\Models\Blood\BloodUnit;
use App\Models\Branch;
use App\Models\User;
use App\Services\Numbering\NumberSeriesService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** A donation, from the bag being identified to the post-donation rest; collecting it creates the quarantined whole-blood unit. */
class BloodDonationService
{
    public function __construct(
        private readonly NumberSeriesService $numbers,
        private readonly BloodPolicy $policy,
        private readonly BloodUnitService $units,
        private readonly BloodTestingService $testing,
    ) {}

    public function start(BloodDonor $donor, BloodDonorScreening $screening, Branch $branch, User $by, ?string $bagLot = null, ?int $replacementRequestId = null): BloodDonation
    {
        return DB::transaction(function () use ($donor, $screening, $branch, $by, $bagLot, $replacementRequestId) {
            $locked = BloodDonor::query()->lockForUpdate()->findOrFail($donor->id);
            if ($locked->isDeferred() || $locked->status === BloodDonor::INACTIVE) {
                throw ValidationException::withMessages(['donor' => 'This donor is deferred or inactive and cannot donate.']);
            }
            if ($screening->donor_id !== $locked->id || $screening->outcome !== 'eligible' || $screening->screened_at->lt(now()->subDay())) {
                throw ValidationException::withMessages(['screening' => 'A passed screening from the last 24 hours is needed before collection.']);
            }
            if (BloodDonation::query()->where('screening_id', $screening->id)->exists()) {
                throw ValidationException::withMessages(['screening' => 'This screening has already been used for a donation.']);
            }
            if (BloodDonation::query()->where('donor_id', $locked->id)->where('status', 'collecting')->exists()) {
                throw ValidationException::withMessages(['donor' => 'A donation is already in progress for this donor.']);
            }

            return BloodDonation::create([
                'organization_id' => $branch->organization_id, 'branch_id' => $branch->id, 'donor_id' => $locked->id, 'screening_id' => $screening->id, 'bag_lot' => $bagLot,
                'donation_number' => $this->numbers->next('blood.donation', $branch->organization_id, null, prefix: 'DON', format: '{PREFIX}-{YEAR}-{SEQ:8}', userId: $by->id),
                'replacement_request_id' => $replacementRequestId, 'status' => 'collecting', 'started_at' => now(), 'collected_by' => $by->id,
            ]);
        });
    }

    /** Finishes the collection: creates the whole-blood unit (quarantined until tested), orders the tests, starts the rest period. */
    public function complete(BloodDonation $donation, int $volumeMl, User $by): BloodDonation
    {
        return DB::transaction(function () use ($donation, $volumeMl, $by) {
            $locked = BloodDonation::query()->lockForUpdate()->findOrFail($donation->id);
            if ($locked->status !== 'collecting') {
                throw ValidationException::withMessages(['donation' => 'This donation is not being collected.']);
            }
            if ($volumeMl < 100 || $volumeMl > 600) {
                throw ValidationException::withMessages(['volume_ml' => 'The collected volume is not plausible.']);
            }

            $component = $this->policy->component('whole_blood');
            $unit = BloodUnit::create([
                'organization_id' => $locked->organization_id, 'branch_id' => $locked->branch_id, 'donation_id' => $locked->id, 'component' => 'whole_blood',
                'unit_number' => $locked->donation_number.'-00', 'volume_ml' => $volumeMl, 'status' => BloodUnit::QUARANTINED,
                'location_id' => $this->quarantine($locked->organization_id)?->id, 'collected_at' => now(), 'expires_at' => now()->addDays($component['shelf_days']),
            ]);
            $this->units->event($unit, 'collected', $by, "Donation {$locked->donation_number}", $locked, null, BloodUnit::QUARANTINED);

            $locked->update([
                'status' => 'collected', 'volume_ml' => $volumeMl, 'collected_at' => now(), 'collected_by' => $by->id,
                'observation_until' => now()->addMinutes((int) $this->policy->get('post_donation_observation_minutes')),
            ]);
            BloodDonor::query()->whereKey($locked->donor_id)->update(['last_donation_at' => now()]);
            BloodDonor::query()->whereKey($locked->donor_id)->increment('donation_count');

            $this->testing->orderTests($locked->refresh(), $by);

            return $locked->refresh();
        });
    }

    public function abort(BloodDonation $donation, string $reason): BloodDonation
    {
        if ($donation->status !== 'collecting') {
            throw ValidationException::withMessages(['donation' => 'Only a donation in progress can be stopped.']);
        }
        $donation->update(['status' => 'aborted', 'abort_reason' => $reason]);

        return $donation->refresh();
    }

    public function finishObservation(BloodDonation $donation): BloodDonation
    {
        $donation->update(['observation_done_at' => now()]);

        return $donation->refresh();
    }

    public function recordAdverseEvent(BloodDonation $donation, User $by, string $type, string $severity, ?string $description = null, ?string $action = null): BloodDonorAdverseEvent
    {
        if (! in_array($type, $this->policy->get('adverse_event_types'), true)) {
            throw ValidationException::withMessages(['event_type' => 'Not a known type of donor reaction.']);
        }

        return BloodDonorAdverseEvent::create([
            'organization_id' => $donation->organization_id, 'donation_id' => $donation->id, 'event_type' => $type, 'severity' => $severity,
            'description' => $description, 'action_taken' => $action, 'occurred_at' => now(), 'recorded_by' => $by->id,
        ]);
    }

    private function quarantine(int $organizationId): ?BloodStorageLocation
    {
        return BloodStorageLocation::query()->where('organization_id', $organizationId)->where('kind', 'quarantine')->where('is_active', true)->first();
    }
}
