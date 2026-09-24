<?php

namespace App\Events\Ipd;

use App\Models\Ipd\IpdBedBlock;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BedUnblocked
{
    use Dispatchable, SerializesModels;

    public function __construct(public IpdBedBlock $block) {}
}
