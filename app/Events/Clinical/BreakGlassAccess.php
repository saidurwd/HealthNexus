<?php

namespace App\Events\Clinical;

use App\Models\BreakGlassAccess;
use App\Models\Encounter;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BreakGlassAccess
{
    use Dispatchable, SerializesModels;

    public function __construct(public Encounter $encounter, public BreakGlassAccess $access) {}
}
