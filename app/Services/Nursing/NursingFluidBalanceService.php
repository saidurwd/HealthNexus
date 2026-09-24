<?php

namespace App\Services\Nursing;

use App\Models\Nursing\NursingEpisode;
use App\Models\Nursing\NursingIntakeOutputRecord;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Net balance is always computed on read over a caller-supplied period, never stored as a
 * running total (spec §20) — every intake/output entry is an immutable, append-only record.
 */
class NursingFluidBalanceService
{
    public function recordIntake(NursingEpisode $episode, array $data, User $user): NursingIntakeOutputRecord
    {
        return $this->record($episode, NursingIntakeOutputRecord::TYPE_INTAKE, $data, $user);
    }

    public function recordOutput(NursingEpisode $episode, array $data, User $user): NursingIntakeOutputRecord
    {
        return $this->record($episode, NursingIntakeOutputRecord::TYPE_OUTPUT, $data, $user);
    }

    public function netBalance(NursingEpisode $episode, Carbon $from, Carbon $to): array
    {
        $rows = NursingIntakeOutputRecord::query()
            ->where('episode_id', $episode->id)
            ->whereBetween('recorded_at', [$from, $to])
            ->get();

        $intake = (float) $rows->where('type', NursingIntakeOutputRecord::TYPE_INTAKE)->sum('amount');
        $output = (float) $rows->where('type', NursingIntakeOutputRecord::TYPE_OUTPUT)->sum('amount');

        return [
            'total_intake' => $intake,
            'total_output' => $output,
            'net_balance' => $intake - $output,
        ];
    }

    private function record(NursingEpisode $episode, string $type, array $data, User $user): NursingIntakeOutputRecord
    {
        return NursingIntakeOutputRecord::create([
            ...$data,
            'company_id' => $episode->company_id,
            'branch_id' => $episode->branch_id,
            'episode_id' => $episode->id,
            'admission_id' => $episode->admission_id,
            'patient_id' => $episode->patient_id,
            'type' => $type,
            'recorded_at' => $data['recorded_at'] ?? now(),
            'recorded_by' => $user->id,
        ]);
    }
}
