<?php

namespace App\Services\Ot;

use App\Events\Ot\SurgicalComplicationRecorded;
use App\Models\Ot\OtSurgery;
use App\Models\Ot\OtSurgicalComplication;
use App\Models\User;

class OtSurgicalComplicationService
{
    public function record(OtSurgery $surgery, array $data, User $user): OtSurgicalComplication
    {
        $complication = OtSurgicalComplication::create([
            ...$data,
            'surgery_id' => $surgery->id,
            'occurred_at' => $data['occurred_at'] ?? now(),
            'reported_by' => $user->id,
        ]);

        event(new SurgicalComplicationRecorded($complication));

        return $complication;
    }
}
