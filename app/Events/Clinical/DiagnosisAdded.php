<?php

namespace App\Events\Clinical;

use App\Models\Encounter;
use App\Models\EncounterComplaint;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DiagnosisAdded
{
    use Dispatchable, SerializesModels;

    public function __construct(public Encounter $encounter, public EncounterComplaint $diagnosis) {}
}
