<?php

namespace App\Events\Clinical;

use App\Models\Diagnosis;
use App\Models\Encounter;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DiagnosisAdded
{
    use Dispatchable, SerializesModels;

    public function __construct(public Encounter $encounter, public Diagnosis $diagnosis) {}
}
