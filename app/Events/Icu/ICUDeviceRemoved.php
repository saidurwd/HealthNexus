<?php

namespace App\Events\Icu;

use App\Models\Icu\IcuDevice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ICUDeviceRemoved
{
    use Dispatchable, SerializesModels;

    public function __construct(public IcuDevice $device) {}
}
