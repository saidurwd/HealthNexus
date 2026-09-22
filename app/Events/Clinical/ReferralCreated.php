<?php

namespace App\Events\Clinical;

use App\Models\Encounter;
use App\Models\EncounterReferral;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReferralCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Encounter $encounter, public EncounterReferral $referral) {}
}
