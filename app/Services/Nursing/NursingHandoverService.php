<?php

namespace App\Services\Nursing;

use App\Events\Nursing\NursingHandoverAcknowledged;
use App\Events\Nursing\NursingHandoverCreated;
use App\Models\Nursing\NursingEpisode;
use App\Models\Nursing\NursingHandover;
use App\Models\Nursing\NursingHandoverItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * prepare() snapshots live data into items at that moment (spec §10: never an AI-generated
 * conclusion, only factual pulls) — the resulting nursing_handover_items rows are then frozen,
 * so a historical handover stays readable even as the underlying records change later.
 */
class NursingHandoverService
{
    public function prepare(NursingEpisode $episode, User $outgoingNurse, ?int $shiftId = null): NursingHandover
    {
        $handover = NursingHandover::create([
            'company_id' => $episode->company_id,
            'branch_id' => $episode->branch_id,
            'episode_id' => $episode->id,
            'admission_id' => $episode->admission_id,
            'outgoing_nurse_id' => $outgoingNurse->id,
            'shift_id' => $shiftId,
            'status' => NursingHandover::STATUS_DRAFT,
            'prepared_at' => now(),
        ]);

        $this->addItem($handover, 'identity', $episode->patient?->full_name ?? (string) $episode->patient_id);

        if ($episode->admission) {
            $this->addItem($handover, 'condition', "Admission {$episode->admission->admission_number}, status {$episode->admission->status}");
        }

        $latestVital = $episode->encounter?->vitals()->latest('recorded_at')->first();
        if ($latestVital) {
            $this->addItem($handover, 'observations', "Last vitals recorded ".$latestVital->recorded_at->diffForHumans());
        }

        $pendingMar = $episode->admission_id
            ? \App\Models\Nursing\NursingMedicationAdministration::where('episode_id', $episode->id)
                ->whereIn('status', ['scheduled', 'due'])
                ->count()
            : 0;
        $this->addItem($handover, 'pending_orders', "{$pendingMar} medication administration(s) pending");

        $activeDevices = \App\Models\Nursing\NursingDevice::where('episode_id', $episode->id)
            ->where('status', 'active')->pluck('device_type');
        if ($activeDevices->isNotEmpty()) {
            $this->addItem($handover, 'devices', $activeDevices->implode(', '));
        }

        return $handover->fresh('items');
    }

    public function addItem(NursingHandover $handover, string $section, string $content, ?string $sourceType = null, ?int $sourceId = null): NursingHandoverItem
    {
        return $handover->items()->create([
            'section' => $section,
            'content' => $content,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
        ]);
    }

    public function finalize(NursingHandover $handover, ?User $incomingNurse = null): NursingHandover
    {
        $handover->update([
            'status' => NursingHandover::STATUS_PENDING_ACKNOWLEDGEMENT,
            'incoming_nurse_id' => $incomingNurse?->id ?? $handover->incoming_nurse_id,
        ]);

        event(new NursingHandoverCreated($handover));

        return $handover->refresh();
    }

    public function acknowledge(NursingHandover $handover, User $incomingNurse): NursingHandover
    {
        DB::transaction(function () use ($handover, $incomingNurse) {
            $locked = NursingHandover::query()->lockForUpdate()->findOrFail($handover->id);

            if ($locked->status === NursingHandover::STATUS_ACKNOWLEDGED) {
                throw ValidationException::withMessages(['handover' => 'This handover has already been acknowledged.']);
            }

            $locked->update([
                'status' => NursingHandover::STATUS_ACKNOWLEDGED,
                'incoming_nurse_id' => $incomingNurse->id,
                'acknowledged_at' => now(),
            ]);
        });

        $handover->refresh();
        event(new NursingHandoverAcknowledged($handover));

        return $handover;
    }
}
