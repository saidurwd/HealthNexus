<?php

namespace App\Events\Nursing;

use App\Models\Nursing\NursingObservation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a recorded observation breaches a configured threshold. This is a mechanical
 * threshold check, never a clinical conclusion (spec §15: "An alert is not automatically a
 * diagnosis") — consumers may raise a NursingAlert but must never create a diagnosis.
 */
class CriticalObservationDetected
{
    use Dispatchable, SerializesModels;

    public function __construct(public NursingObservation $observation, public string $breach) {}
}
