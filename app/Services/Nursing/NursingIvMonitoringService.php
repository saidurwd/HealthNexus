<?php

namespace App\Services\Nursing;

use App\Models\Nursing\NursingEpisode;
use App\Models\Nursing\NursingIvInfusion;
use App\Models\User;

class NursingIvMonitoringService
{
    public function start(NursingEpisode $episode, array $data, User $user): NursingIvInfusion
    {
        return NursingIvInfusion::create([
            ...$data,
            'company_id' => $episode->company_id,
            'branch_id' => $episode->branch_id,
            'episode_id' => $episode->id,
            'admission_id' => $episode->admission_id,
            'status' => NursingIvInfusion::STATUS_RUNNING,
            'monitored_by' => $user->id,
        ]);
    }

    public function complete(NursingIvInfusion $infusion, ?string $complications = null): NursingIvInfusion
    {
        $infusion->update([
            'status' => NursingIvInfusion::STATUS_COMPLETED,
            'actual_completion' => now(),
            'complications' => $complications ?? $infusion->complications,
        ]);

        return $infusion->refresh();
    }

    public function stop(NursingIvInfusion $infusion, string $complications): NursingIvInfusion
    {
        $infusion->update([
            'status' => NursingIvInfusion::STATUS_STOPPED,
            'actual_completion' => now(),
            'complications' => $complications,
        ]);

        return $infusion->refresh();
    }
}
