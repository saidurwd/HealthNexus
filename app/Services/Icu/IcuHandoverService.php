<?php

namespace App\Services\Icu;

use App\Events\Icu\ICUHandoverAcknowledged;
use App\Models\Icu\IcuDevice;
use App\Models\Icu\IcuEpisode;
use App\Models\Icu\IcuHandoverRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * prepare() snapshots live data into a frozen sections JSON blob at that moment — never an
 * AI-generated conclusion, only factual pulls, mirroring NursingHandoverService::prepare().
 */
class IcuHandoverService
{
    public function prepare(IcuEpisode $episode, User $outgoingUser, string $handoverType = IcuHandoverRecord::TYPE_SHIFT): IcuHandoverRecord
    {
        $latestObservation = $episode->observations()->latest('observed_at')->first();
        $activeDevices = IcuDevice::where('episode_id', $episode->id)->where('status', IcuDevice::STATUS_ACTIVE)->pluck('device_type');
        $activeInfusions = $episode->infusions()->where('status', 'running')->pluck('medication');

        $sections = [
            'patient' => $episode->patient?->full_name ?? (string) $episode->patient_id,
            'diagnosis' => $episode->primaryDiagnosis?->name,
            'current_condition' => $episode->status,
            'observations' => $latestObservation ? 'Last observation recorded '.$latestObservation->observed_at->diffForHumans() : 'No observations recorded yet',
            'lines' => $activeDevices->implode(', ') ?: 'None',
            'medications' => $activeInfusions->implode(', ') ?: 'None',
            'pending_orders' => '',
            'risks' => '',
            'plan' => '',
        ];

        return IcuHandoverRecord::create([
            'company_id' => $episode->company_id,
            'branch_id' => $episode->branch_id,
            'episode_id' => $episode->id,
            'outgoing_user_id' => $outgoingUser->id,
            'handover_type' => $handoverType,
            'sections' => $sections,
            'status' => IcuHandoverRecord::STATUS_DRAFT,
            'prepared_at' => now(),
        ]);
    }

    public function finalize(IcuHandoverRecord $handover): IcuHandoverRecord
    {
        $handover->update(['status' => IcuHandoverRecord::STATUS_PENDING_ACKNOWLEDGEMENT]);

        return $handover->refresh();
    }

    public function acknowledge(IcuHandoverRecord $handover, User $incomingUser): IcuHandoverRecord
    {
        DB::transaction(function () use ($handover, $incomingUser) {
            $locked = IcuHandoverRecord::query()->lockForUpdate()->findOrFail($handover->id);

            if ($locked->status === IcuHandoverRecord::STATUS_ACKNOWLEDGED) {
                throw ValidationException::withMessages(['handover' => 'This handover has already been acknowledged.']);
            }

            $locked->update([
                'status' => IcuHandoverRecord::STATUS_ACKNOWLEDGED,
                'incoming_user_id' => $incomingUser->id,
                'acknowledged_at' => now(),
            ]);
        });

        $handover->refresh();
        event(new ICUHandoverAcknowledged($handover));

        return $handover;
    }
}
