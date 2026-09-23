<?php

namespace App\Jobs\Radiology;

use App\Models\Radiology\RadiologyDicomEvent;
use App\Models\Radiology\RadiologyExamination;
use App\Models\Radiology\RadiologyPacsServer;
use App\Models\Radiology\RadiologySeries;
use App\Models\Radiology\RadiologyStudy;
use App\Services\Radiology\Pacs\PacsClientFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * Reconciles a completed examination against the configured PACS (spec §67/§68) — never
 * silently alters patient identity; unmatched/duplicate studies are flagged for authorized
 * review, not auto-resolved. Dispatched on examination completion, and manually re-triggerable
 * ("Check for images").
 */
class SyncPacsStudyMetadata implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $examinationId) {}

    public function handle(PacsClientFactory $factory): void
    {
        $examination = RadiologyExamination::find($this->examinationId);

        if (! $examination) {
            return;
        }

        $order = $examination->orderItem->radiologyOrder;
        $server = RadiologyPacsServer::query()
            ->forTenant($examination->company_id, $examination->branch_id)
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();

        if (! $server) {
            $this->logEvent($examination, null, 'skipped', 'No active default PACS server configured.');

            return;
        }

        $client = $factory->for($server);
        $found = $client->findStudy($order->accession_number);

        if (! $found) {
            $this->logEvent($examination, $server, 'failed', 'No matching study found on PACS for accession '.$order->accession_number);

            return;
        }

        DB::transaction(function () use ($examination, $server, $found, $order) {
            $existingByUid = RadiologyStudy::query()->where('study_instance_uid', $found['study_instance_uid'])->first();

            if ($existingByUid && $existingByUid->radiology_examination_id !== $examination->id) {
                // Same Study Instance UID already reconciled against a different examination —
                // flag for review rather than silently reassigning it (spec §67/§68).
                $this->logEvent($examination, $server, 'failed', "Duplicate Study Instance UID {$found['study_instance_uid']} already linked to examination #{$existingByUid->radiology_examination_id}.");

                return;
            }

            $study = RadiologyStudy::query()->updateOrCreate(
                ['radiology_examination_id' => $examination->id],
                [
                    'company_id' => $examination->company_id,
                    'branch_id' => $examination->branch_id,
                    'patient_id' => $examination->patient_id,
                    'pacs_server_id' => $server->id,
                    'accession_number' => $order->accession_number,
                    'study_instance_uid' => $found['study_instance_uid'],
                    'study_date' => $found['study_date'] ?? null,
                    'modality' => $found['modality'] ?? null,
                    'study_description' => $found['study_description'] ?? null,
                    'pacs_status' => 'found',
                    'study_status' => 'reconciled',
                    'number_of_series' => $found['number_of_series'] ?? 0,
                    'number_of_instances' => $found['number_of_instances'] ?? 0,
                    'last_synced_at' => now(),
                ],
            );

            $metadata = $client->getStudyMetadata($found['study_instance_uid']);

            foreach ($metadata['series'] ?? [] as $seriesData) {
                RadiologySeries::query()->updateOrCreate(
                    ['series_instance_uid' => $seriesData['series_instance_uid']],
                    [
                        'study_id' => $study->id,
                        'series_number' => $seriesData['series_number'] ?? null,
                        'series_description' => $seriesData['series_description'] ?? null,
                        'number_of_instances' => $seriesData['number_of_instances'] ?? 0,
                    ],
                );
            }

            if (in_array($examination->status, ['completed'], true)) {
                app(\App\Services\Radiology\RadiologyOrderLifecycleService::class)->transitionTo($order->fresh(), 'images_available');
            }

            $this->logEvent($examination, $server, 'success', "Reconciled study {$found['study_instance_uid']}.");
        });
    }

    private function logEvent(RadiologyExamination $examination, ?RadiologyPacsServer $server, string $status, string $summary): void
    {
        RadiologyDicomEvent::create([
            'company_id' => $examination->company_id,
            'branch_id' => $examination->branch_id,
            'radiology_examination_id' => $examination->id,
            'pacs_server_id' => $server?->id,
            'event_type' => 'study_sync',
            'status' => $status,
            'summary' => $summary,
        ]);
    }
}
