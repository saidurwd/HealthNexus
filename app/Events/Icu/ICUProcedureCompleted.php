<?php

namespace App\Events\Icu;

use App\Models\Icu\IcuProcedureRecord;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ICUProcedureCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public IcuProcedureRecord $record) {}
}
