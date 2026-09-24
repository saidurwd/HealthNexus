<?php

namespace App\Services\Nursing;

use App\Events\Nursing\MedicationAdministrationRecorded;
use App\Events\Nursing\MedicationHeld;
use App\Events\Nursing\MedicationOmitted;
use App\Events\Nursing\MedicationRefused;
use App\Models\Ipd\IpdAdmission;
use App\Models\Nursing\NursingEpisode;
use App\Models\Nursing\NursingMedicationAdministration;
use App\Models\Nursing\NursingMedicationAdministrationCorrection;
use App\Models\Pharmacy\PharmacyDispensingItem;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The highest-risk concurrency surface in Phase 9 (spec §84/§54): administer()/hold()/refuse()/
 * omit() all lock the MAR row inside a transaction and re-verify it is still actionable under the
 * lock, mirroring IpdBedAllocationService::allocate()'s shape — this is what guarantees that of
 * two simultaneous administration attempts on the same MAR item, exactly one succeeds.
 */
class MedicationAdministrationService
{
    public function __construct(private readonly SettingsService $settings) {}

    public function createScheduled(NursingEpisode $episode, PharmacyDispensingItem $dispensingItem, array $data = []): NursingMedicationAdministration
    {
        $orderItem = $dispensingItem->orderItem;
        $prescriptionItem = $orderItem?->prescriptionItem;

        return NursingMedicationAdministration::create([
            ...$data,
            'company_id' => $episode->company_id,
            'branch_id' => $episode->branch_id,
            'episode_id' => $episode->id,
            'admission_id' => $episode->admission_id,
            'encounter_id' => $episode->encounter_id,
            'patient_id' => $episode->patient_id,
            'prescription_id' => $prescriptionItem?->prescription_id,
            'prescription_item_id' => $prescriptionItem?->id,
            'dispensing_item_id' => $dispensingItem->id,
            'medication_id' => $dispensingItem->medication_id,
            'batch_id' => $dispensingItem->batch_id,
            'scheduled_at' => $data['scheduled_at'] ?? now(),
            'route' => $data['route'] ?? $prescriptionItem?->dosage_form,
            'dose' => $data['dose'] ?? $prescriptionItem?->strength,
            'status' => NursingMedicationAdministration::STATUS_SCHEDULED,
        ]);
    }

    /**
     * Administration without any prior dispensing (spec §59) — always explicit, always audited by
     * the caller via a dedicated permission (nursing.mar.administer_without_dispensing), never a
     * silent bypass.
     */
    public function createAdHoc(NursingEpisode $episode, array $data, User $user): NursingMedicationAdministration
    {
        return NursingMedicationAdministration::create([
            ...$data,
            'company_id' => $episode->company_id,
            'branch_id' => $episode->branch_id,
            'episode_id' => $episode->id,
            'admission_id' => $episode->admission_id,
            'encounter_id' => $episode->encounter_id,
            'patient_id' => $episode->patient_id,
            'scheduled_at' => $data['scheduled_at'] ?? now(),
            'status' => NursingMedicationAdministration::STATUS_SCHEDULED,
            'created_by' => $user->id,
        ]);
    }

    public function administer(NursingMedicationAdministration $mar, User $user, array $safetyChecks, array $data = [], ?User $witness = null): NursingMedicationAdministration
    {
        $updated = DB::transaction(function () use ($mar, $user, $safetyChecks, $data, $witness) {
            $locked = NursingMedicationAdministration::query()->lockForUpdate()->findOrFail($mar->id);

            $this->assertActionable($locked);

            $medication = $locked->medication;
            $requiresWitness = ($medication?->is_controlled && $this->settings->get('nursing.controlled_requires_witness', true))
                || ($medication?->is_high_alert && $this->settings->get('nursing.high_alert_requires_witness', true));

            if ($requiresWitness && ! $witness) {
                throw ValidationException::withMessages(['witness' => 'A second-nurse witness is required to administer this medication.']);
            }

            $dose = $data['dose'] ?? $locked->dose;
            $route = $data['route'] ?? $locked->route;

            if (! $dose || ! $route) {
                throw ValidationException::withMessages(['dose' => 'Dose and route must be confirmed before administration.']);
            }

            $locked->update([
                'dose' => $dose,
                'route' => $route,
                'site' => $data['site'] ?? $locked->site,
                'status' => NursingMedicationAdministration::STATUS_ADMINISTERED,
                'administered_at' => now(),
                'administered_by' => $user->id,
                'witnessed_by' => $witness?->id,
                'safety_checks' => $safetyChecks,
                'notes' => $data['notes'] ?? $locked->notes,
            ]);

            return $locked;
        });

        event(new MedicationAdministrationRecorded($updated));

        return $updated;
    }

    public function hold(NursingMedicationAdministration $mar, User $user, string $reason): NursingMedicationAdministration
    {
        $updated = $this->terminate($mar, NursingMedicationAdministration::STATUS_HELD, $reason);
        event(new MedicationHeld($updated));

        return $updated;
    }

    public function refuse(NursingMedicationAdministration $mar, User $user, string $reason): NursingMedicationAdministration
    {
        $updated = $this->terminate($mar, NursingMedicationAdministration::STATUS_REFUSED, $reason);
        event(new MedicationRefused($updated));

        return $updated;
    }

    public function omit(NursingMedicationAdministration $mar, User $user, string $reason): NursingMedicationAdministration
    {
        $updated = $this->terminate($mar, NursingMedicationAdministration::STATUS_OMITTED, $reason);
        event(new MedicationOmitted($updated));

        return $updated;
    }

    /**
     * Never mutates the original administration's clinical fields directly — writes a correction
     * row and only sets a superseded_by_correction_id marker (spec §62).
     */
    public function correct(NursingMedicationAdministration $mar, array $changes, string $reason, User $user): NursingMedicationAdministrationCorrection
    {
        if (! in_array($mar->status, NursingMedicationAdministration::TERMINAL_STATUSES, true)) {
            throw ValidationException::withMessages(['administration' => 'Only a finalized administration can be corrected.']);
        }

        return DB::transaction(function () use ($mar, $changes, $reason, $user) {
            $correction = NursingMedicationAdministrationCorrection::create([
                'administration_id' => $mar->id,
                'correction_type' => $changes['correction_type'] ?? 'amendment',
                'reason' => $reason,
                'previous_values' => $mar->only(['dose', 'dose_unit', 'route', 'site', 'status', 'notes']),
                'created_by' => $user->id,
            ]);

            $mar->update(['superseded_by_correction_id' => $correction->id]);

            return $correction;
        });
    }

    private function terminate(NursingMedicationAdministration $mar, string $status, string $reason): NursingMedicationAdministration
    {
        return DB::transaction(function () use ($mar, $status, $reason) {
            $locked = NursingMedicationAdministration::query()->lockForUpdate()->findOrFail($mar->id);

            $this->assertActionable($locked);

            $locked->update(['status' => $status, 'reason_if_not_administered' => $reason]);

            return $locked;
        });
    }

    private function assertActionable(NursingMedicationAdministration $mar): void
    {
        if (in_array($mar->status, NursingMedicationAdministration::TERMINAL_STATUSES, true)) {
            throw ValidationException::withMessages(['administration' => "This medication administration is already '{$mar->status}' and cannot be actioned again."]);
        }
    }
}
