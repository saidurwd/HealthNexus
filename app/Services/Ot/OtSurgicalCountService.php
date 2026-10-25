<?php

namespace App\Services\Ot;

use App\Models\Ot\OtSurgery;
use App\Models\Ot\OtSurgicalCount;
use App\Models\User;

class OtSurgicalCountService
{
    public function record(OtSurgery $surgery, array $data, User $user): OtSurgicalCount
    {
        return OtSurgicalCount::create([
            ...$data,
            'surgery_id' => $surgery->id,
            'counted_by' => $user->id,
            'counted_at' => $data['counted_at'] ?? now(),
        ]);
    }
}
