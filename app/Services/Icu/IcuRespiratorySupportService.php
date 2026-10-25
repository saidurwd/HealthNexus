<?php

namespace App\Services\Icu;

use App\Models\Icu\IcuEpisode;
use App\Models\Icu\IcuRespiratorySupport;
use App\Models\User;

class IcuRespiratorySupportService
{
    public function start(IcuEpisode $episode, array $data, User $user): IcuRespiratorySupport
    {
        return IcuRespiratorySupport::create([
            ...$data,
            'organization_id' => $episode->organization_id,
            'branch_id' => $episode->branch_id,
            'episode_id' => $episode->id,
            'started_at' => $data['started_at'] ?? now(),
            'started_by' => $user->id,
        ]);
    }

    public function update(IcuRespiratorySupport $support, array $data): IcuRespiratorySupport
    {
        $support->update($data);

        return $support->refresh();
    }

    public function stop(IcuRespiratorySupport $support, User $user): IcuRespiratorySupport
    {
        $support->update(['stopped_at' => now(), 'stopped_by' => $user->id]);

        return $support->refresh();
    }
}
