<?php

namespace App\Services\Icu;

use App\Events\Icu\ICUInfusionStarted;
use App\Events\Icu\ICUInfusionStopped;
use App\Events\Icu\ICUInfusionTitrated;
use App\Models\Icu\IcuEpisode;
use App\Models\Icu\IcuInfusion;
use App\Models\Icu\IcuInfusionTitration;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class IcuInfusionService
{
    public function start(IcuEpisode $episode, array $data, User $user): IcuInfusion
    {
        $infusion = IcuInfusion::create([
            ...$data,
            'company_id' => $episode->company_id,
            'branch_id' => $episode->branch_id,
            'episode_id' => $episode->id,
            'started_at' => $data['started_at'] ?? now(),
            'started_by' => $user->id,
            'status' => IcuInfusion::STATUS_RUNNING,
        ]);

        event(new ICUInfusionStarted($infusion));

        return $infusion;
    }

    public function titrate(IcuInfusion $infusion, float $newRate, User $user, ?string $reason = null, ?string $rateUnit = null): IcuInfusionTitration
    {
        if ($infusion->status !== IcuInfusion::STATUS_RUNNING) {
            throw ValidationException::withMessages(['infusion' => 'This infusion is not currently running.']);
        }

        $titration = $infusion->titrations()->create([
            'previous_rate' => $infusion->currentRate(),
            'new_rate' => $newRate,
            'rate_unit' => $rateUnit ?? $infusion->rate_unit,
            'reason' => $reason,
            'changed_by' => $user->id,
            'changed_at' => now(),
        ]);

        event(new ICUInfusionTitrated($infusion->fresh()));

        return $titration;
    }

    public function stop(IcuInfusion $infusion, User $user): IcuInfusion
    {
        if ($infusion->status !== IcuInfusion::STATUS_RUNNING) {
            throw ValidationException::withMessages(['infusion' => 'This infusion is already stopped.']);
        }

        $infusion->update([
            'status' => IcuInfusion::STATUS_STOPPED,
            'stopped_at' => now(),
            'stopped_by' => $user->id,
        ]);

        event(new ICUInfusionStopped($infusion->fresh()));

        return $infusion->refresh();
    }
}
