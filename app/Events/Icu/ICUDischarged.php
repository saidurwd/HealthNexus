<?php

namespace App\Events\Icu;

use App\Models\Icu\IcuEpisode;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ICUDischarged
{
    use Dispatchable, SerializesModels;

    public function __construct(public IcuEpisode $episode) {}
}
