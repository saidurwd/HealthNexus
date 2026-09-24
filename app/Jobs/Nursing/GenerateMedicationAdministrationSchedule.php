<?php

namespace App\Jobs\Nursing;

use App\Models\Nursing\NursingMedicationAdministration;
use App\Services\SettingsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Best-effort only (decision #8 of the Phase 9 plan, spec §14/§35's prohibition on hardcoded
 * universal clinical frequency rules) — generates the single next dose for a just-administered,
 * non-PRN item whose prescription frequency text matches a hospital-configured code in
 * nursing.mar_frequency_intervals. Anything unrecognized, and all PRN items, are left alone;
 * nurse-initiated "Schedule Next Dose" is the primary path regardless. Idempotent: skips any
 * prescription item that already has a future scheduled/due row.
 */
class GenerateMedicationAdministrationSchedule implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(SettingsService $settings): void
    {
        $intervals = $settings->get('nursing.mar_frequency_intervals', []);

        if (empty($intervals)) {
            return;
        }

        NursingMedicationAdministration::query()
            ->where('status', NursingMedicationAdministration::STATUS_ADMINISTERED)
            ->where('is_prn', false)
            ->whereNotNull('prescription_item_id')
            ->whereNotNull('administered_at')
            ->with('prescriptionItem')
            ->get()
            ->groupBy('prescription_item_id')
            ->each(function ($rows) use ($intervals) {
                $latest = $rows->sortByDesc('administered_at')->first();
                $frequency = strtoupper(trim((string) $latest->prescriptionItem?->frequency));

                if (! isset($intervals[$frequency])) {
                    return;
                }

                $hasFuture = NursingMedicationAdministration::query()
                    ->where('prescription_item_id', $latest->prescription_item_id)
                    ->whereIn('status', [NursingMedicationAdministration::STATUS_SCHEDULED, NursingMedicationAdministration::STATUS_DUE])
                    ->exists();

                if ($hasFuture) {
                    return;
                }

                $latest->replicate(['status', 'administered_at', 'administered_by', 'witnessed_by', 'safety_checks', 'notes'])
                    ->fill([
                        'status' => NursingMedicationAdministration::STATUS_SCHEDULED,
                        'scheduled_at' => $latest->administered_at->copy()->addHours((int) $intervals[$frequency]),
                        'administered_at' => null,
                        'administered_by' => null,
                        'witnessed_by' => null,
                        'safety_checks' => null,
                        'notes' => null,
                        'superseded_by_correction_id' => null,
                    ])
                    ->save();
            });
    }
}
