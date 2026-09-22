<?php

namespace App\Events\Clinical;

use App\Models\ClinicalOrder;
use App\Models\Encounter;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClinicalOrderCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Encounter $encounter, public ClinicalOrder $order) {}
}
