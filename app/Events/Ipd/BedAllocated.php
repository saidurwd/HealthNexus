<?php

namespace App\Events\Ipd;

use App\Models\Ipd\IpdBedAllocation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BedAllocated
{
    use Dispatchable, SerializesModels;

    public function __construct(public IpdBedAllocation $allocation) {}
}
