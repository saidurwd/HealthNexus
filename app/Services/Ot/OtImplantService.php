<?php

namespace App\Services\Ot;

use App\Events\Ot\ImplantRecorded;
use App\Models\Ot\OtImplant;
use App\Models\Ot\OtSurgery;
use App\Models\User;

class OtImplantService
{
    public function __construct(private readonly OtChargeService $charges) {}

    public function record(OtSurgery $surgery, array $data, User $user): OtImplant
    {
        $implant = OtImplant::create([
            ...$data,
            'surgery_id' => $surgery->id,
            'implanted_at' => $data['implanted_at'] ?? now(),
            'implanted_by' => $user->id,
        ]);

        $charge = $this->charges->chargeImplant($implant, $user);

        if ($charge) {
            $implant->update(['billing_charge_id' => $charge->id]);
        }

        event(new ImplantRecorded($implant->fresh()));

        return $implant->fresh();
    }
}
