<?php

namespace App\Listeners\Radiology;

use App\Events\Radiology\RadiologyExamCompleted;
use App\Jobs\Radiology\SyncPacsStudyMetadata;

class DispatchPacsSyncOnExamCompleted
{
    public function handle(RadiologyExamCompleted $event): void
    {
        SyncPacsStudyMetadata::dispatch($event->examination->id);
    }
}
