<?php

namespace App\Services\Icu;

use App\Models\Icu\IcuEpisode;
use App\Models\Icu\IcuFluidBalanceEntry;
use App\Models\User;
use Carbon\CarbonInterface;

class IcuFluidBalanceService
{
    public function recordEntry(IcuEpisode $episode, array $data, User $user): IcuFluidBalanceEntry
    {
        return IcuFluidBalanceEntry::create([
            ...$data,
            'company_id' => $episode->company_id,
            'branch_id' => $episode->branch_id,
            'episode_id' => $episode->id,
            'recorded_at' => $data['recorded_at'] ?? now(),
            'recorded_by' => $user->id,
        ]);
    }

    /**
     * @return array{intake: float, output: float, balance: float}
     */
    public function balance(IcuEpisode $episode, ?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $query = IcuFluidBalanceEntry::query()->where('episode_id', $episode->id);

        if ($from) {
            $query->where('recorded_at', '>=', $from);
        }

        if ($to) {
            $query->where('recorded_at', '<=', $to);
        }

        $intake = (float) $query->clone()->where('type', IcuFluidBalanceEntry::TYPE_INTAKE)->sum('volume_ml');
        $output = (float) $query->clone()->where('type', IcuFluidBalanceEntry::TYPE_OUTPUT)->sum('volume_ml');

        return ['intake' => $intake, 'output' => $output, 'balance' => $intake - $output];
    }
}
