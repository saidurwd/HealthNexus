<?php

namespace App\Events\Clinical;

use App\Models\Encounter;
use App\Models\Prescription;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PrescriptionIssued
{
    use Dispatchable, SerializesModels;

    public function __construct(public Encounter $encounter, public Prescription $prescription) {}
}
