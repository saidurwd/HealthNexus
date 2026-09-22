<?php

namespace App\Events\Clinical;

use App\Models\Encounter;
use App\Models\EncounterAmendment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClinicalAmendmentCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Encounter $encounter, public EncounterAmendment $amendment) {}
}
