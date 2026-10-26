<?php

namespace App\Services\Blood;

use App\Models\Blood\BloodDonation;
use App\Models\Blood\BloodDonor;
use App\Models\Blood\BloodStorageLocation;
use App\Models\Blood\BloodUnit;
use App\Models\Blood\BloodUnitTest;
use App\Models\Laboratory\LabOrder;
use App\Models\Laboratory\LabOrderItem;
use App\Models\Laboratory\LabResult;
use App\Models\Laboratory\LabTest;
use App\Models\User;
use App\Services\Clinical\Alerts\AlertRequest;
use App\Services\Clinical\Alerts\AlertService;
use App\Services\Clinical\EncounterClinicalService;
use App\Services\EncounterService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Infectious-disease screening and grouping. The tests are Laboratory orders; their results are READ back from the lab (lab_result_id is kept),
 * never typed a second time. A failed screen blocks every unit of the donation; a group that disagrees with the donor's file holds them.
 */
class BloodTestingService
{
    private const PASS = ['negative', 'non_reactive', 'nonreactive', 'not detected', 'non-reactive'];

    public function __construct(
        private readonly BloodPolicy $policy,
        private readonly BloodUnitService $units,
        private readonly EncounterService $encounters,
        private readonly EncounterClinicalService $clinical,
        private readonly AlertService $alerts,
    ) {}

    /** @return list<string> every test a donation needs, from the screening policy plus ABO and Rh */
    public function requiredTests(): array
    {
        return array_values(array_merge(array_keys($this->policy->get('screening_tests')), array_values($this->policy->get('grouping_tests'))));
    }

    public function orderTests(BloodDonation $donation, User $by): void
    {
        DB::transaction(function () use ($donation, $by) {
            foreach ($this->requiredTests() as $code) {
                BloodUnitTest::firstOrCreate(['donation_id' => $donation->id, 'test_code' => $code], ['organization_id' => $donation->organization_id]);
            }

            $donor = BloodDonor::findOrFail($donation->donor_id);
            $encounter = $this->encounters->createEncounter([
                'organization_id' => $donation->organization_id, 'branch_id' => $donation->branch_id, 'patient_id' => $donor->patient_id, 'encounter_type' => 'Blood donation',
            ], $by);
            $labels = $this->policy->get('screening_tests') + ['BB-ABO' => 'Blood group ABO', 'BB-RH' => 'Rh (D) type'];
            $order = $this->clinical->createOrder($encounter, [
                'order_type' => 'laboratory', 'priority' => 'routine', 'provider_id' => $by->id, 'notes' => 'Donation '.$donation->donation_number,
                'items' => array_map(fn ($code) => ['item_code' => $code, 'item_name' => $labels[$code] ?? $code, 'quantity' => 1, 'status' => 'requested'], $this->requiredTests()),
            ], $by);
            $donation->update(['encounter_id' => $encounter->id, 'clinical_order_id' => $order->id]);
        });
    }

    /** Reads the finished results of the donation's lab order and applies them. @return int tests applied */
    public function syncFromLaboratory(BloodDonation $donation): int
    {
        if (! $donation->clinical_order_id) {
            return 0;
        }
        $order = LabOrder::query()->where('clinical_order_id', $donation->clinical_order_id)->first();
        if (! $order) {
            return 0;
        }

        $applied = 0;
        foreach (LabOrderItem::query()->where('lab_order_id', $order->id)->whereNotNull('test_id')->get() as $item) {
            $result = LabResult::query()->where('lab_order_item_id', $item->id)->where('is_current', true)->whereIn('result_status', ['pathologist_validated', 'technically_validated', 'reported'])->first();
            $code = LabTest::query()->whereKey($item->test_id)->value('code');
            if ($result && in_array($code, $this->requiredTests(), true)) {
                $this->applyResult($donation, $code, (string) ($result->qualitative_value ?? $result->text_value ?? ''), $result->id);
                $applied++;
            }
        }

        return $applied;
    }

    public function applyResult(BloodDonation $donation, string $testCode, string $value, ?int $labResultId = null): BloodUnitTest
    {
        return DB::transaction(function () use ($donation, $testCode, $value, $labResultId) {
            $test = BloodUnitTest::query()->lockForUpdate()->where('donation_id', $donation->id)->where('test_code', $testCode)->first();
            if (! $test) {
                throw ValidationException::withMessages(['test' => "Test [$testCode] is not part of this donation."]);
            }
            $normal = strtolower(trim($value));
            $isScreen = array_key_exists($testCode, $this->policy->get('screening_tests'));
            $outcome = $isScreen ? (in_array($normal, self::PASS, true) ? 'pass' : 'fail') : $this->groupingOutcome($testCode, $normal);

            $test->update(['outcome' => $outcome, 'value' => $value, 'lab_result_id' => $labResultId, 'resulted_at' => now()]);
            $this->evaluate($donation->refresh());

            return $test->refresh();
        });
    }

    private function groupingOutcome(string $testCode, string $value): string
    {
        $valid = $testCode === $this->policy->get('grouping_tests')['abo'] ? array_map('strtolower', $this->policy->get('abo')) : $this->policy->get('rh');

        return in_array($value, $valid, true) ? 'pass' : 'fail';
    }

    private function evaluate(BloodDonation $donation): void
    {
        $tests = BloodUnitTest::query()->where('donation_id', $donation->id)->get()->keyBy('test_code');
        $screening = array_keys($this->policy->get('screening_tests'));

        if (collect($screening)->contains(fn ($c) => ($tests[$c]->outcome ?? null) === 'fail')) {
            $this->blockAll($donation, 'positive_screen', 'A mandatory screening test is reactive');
            BloodDonor::query()->whereKey($donation->donor_id)->update(['status' => BloodDonor::PERMANENTLY_DEFERRED, 'deferral_reason' => 'positive_screen', 'deferred_until' => null]);

            return;
        }

        $abo = $tests[$this->policy->get('grouping_tests')['abo']] ?? null;
        $rh = $tests[$this->policy->get('grouping_tests')['rh']] ?? null;
        if ($abo?->outcome === 'pass' && $rh?->outcome === 'pass') {
            $group = strtoupper($abo->value);
            $rhValue = strtolower($rh->value);
            $donor = BloodDonor::findOrFail($donation->donor_id);
            if ($donor->abo && ($donor->abo !== $group || $donor->rh !== $rhValue)) {
                $abo->update(['outcome' => 'discordant']);
                $this->blockAll($donation, 'discordant_group', "Group {$group} {$rhValue} differs from the donor's file ({$donor->abo} {$donor->rh})");

                return;
            }
            $donor->update(['abo' => $group, 'rh' => $rhValue]);
            BloodUnit::query()->where('donation_id', $donation->id)->whereNull('abo')->update(['abo' => $group, 'rh' => $rhValue]);
        }
    }

    private function blockAll(BloodDonation $donation, string $reason, string $why): void
    {
        $units = BloodUnit::query()->where('donation_id', $donation->id)->whereIn('status', [BloodUnit::QUARANTINED, BloodUnit::AVAILABLE, BloodUnit::RESERVED])->get();
        foreach ($units as $unit) {
            $this->units->move($unit, BloodUnit::BLOCKED, null, $why, null, ['blocked_reason' => $reason]);
        }
        if ($units->isNotEmpty()) {
            $this->alerts->raise(new AlertRequest(
                organizationId: $donation->organization_id, category: 'blood_unit_blocked', severity: 'critical', sourceModule: 'bloodbank', branchId: $donation->branch_id,
                message: "Donation {$donation->donation_number}: {$units->count()} unit(s) blocked — $why.", sourceType: BloodDonation::class, sourceId: $donation->id,
                recipientUserIds: $this->roleUsers($donation->organization_id), dedupKey: "blood-block:{$donation->id}:$reason",
            ));
        }
    }

    /** Moves the donation's tested units from quarantine to the shelf. Needs every test passed, a known group and an unexpired unit. @return int units released */
    public function release(BloodDonation $donation, User $by): int
    {
        $tests = BloodUnitTest::query()->where('donation_id', $donation->id)->get();
        if ($tests->isEmpty() || $tests->contains(fn ($t) => $t->outcome !== 'pass')) {
            throw ValidationException::withMessages(['donation' => 'Every screening and grouping test must be resulted and passed before release.']);
        }

        return DB::transaction(function () use ($donation, $by) {
            $released = 0;
            foreach (BloodUnit::query()->where('donation_id', $donation->id)->where('status', BloodUnit::QUARANTINED)->where('component', '!=', 'whole_blood')->get() as $unit) {
                $this->releaseUnit($unit, $by);
                $released++;
            }
            // A whole-blood unit that was never separated is itself issued as whole blood.
            foreach (BloodUnit::query()->where('donation_id', $donation->id)->where('status', BloodUnit::QUARANTINED)->where('component', 'whole_blood')->get() as $unit) {
                $this->releaseUnit($unit, $by);
                $released++;
            }

            return $released;
        });
    }

    private function releaseUnit(BloodUnit $unit, User $by): void
    {
        if (! $unit->abo || ! $unit->rh) {
            throw ValidationException::withMessages(['unit' => "Unit {$unit->unit_number} has no confirmed group."]);
        }
        $kind = $this->policy->component($unit->component)['storage'];
        $location = BloodStorageLocation::query()->where('organization_id', $unit->organization_id)->where('kind', $kind)->where('is_active', true)->first();
        $this->units->move($unit, BloodUnit::AVAILABLE, $by, 'Released after testing', null, ['released_at' => now(), 'released_by' => $by->id, 'location_id' => $location?->id ?? $unit->location_id]);
    }

    /** @return list<int> */
    public function roleUsers(int $organizationId): array
    {
        return User::query()->where('is_active', true)->whereHas('organizations', fn ($q) => $q->where('organizations.id', $organizationId))
            ->whereHas('roles', fn ($q) => $q->whereIn('name', $this->policy->get('alert_roles')))->limit(30)->pluck('id')->all();
    }
}
