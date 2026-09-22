<?php

namespace App\Events\Clinical;

use App\Models\Encounter;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VitalRecorded
{
    use Dispatchable, SerializesModels;

    public function __construct(public Encounter $encounter, public $vital) {}
}
