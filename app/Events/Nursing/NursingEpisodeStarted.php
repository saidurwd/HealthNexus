<?php

namespace App\Events\Nursing;

use App\Models\Nursing\NursingEpisode;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NursingEpisodeStarted
{
    use Dispatchable, SerializesModels;

    public function __construct(public NursingEpisode $episode) {}
}
