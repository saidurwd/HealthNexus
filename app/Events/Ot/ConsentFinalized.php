<?php

namespace App\Events\Ot;

use App\Models\PatientConsent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConsentFinalized
{
    use Dispatchable, SerializesModels;

    public function __construct(public PatientConsent $consent) {}
}
