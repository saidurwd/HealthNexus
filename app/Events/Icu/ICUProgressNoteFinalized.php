<?php

namespace App\Events\Icu;

use App\Models\Icu\IcuProgressNote;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ICUProgressNoteFinalized
{
    use Dispatchable, SerializesModels;

    public function __construct(public IcuProgressNote $note) {}
}
