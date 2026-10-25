<?php

namespace App\Observers;

use App\Models\Patient;
use App\Services\Search\PatientSearchIndexer;

/** Keeps the search index in step with the patient row (including merges that soft-delete a record). */
class PatientSearchObserver
{
    public function __construct(private readonly PatientSearchIndexer $indexer) {}

    public function saved(Patient $patient): void
    {
        $patient->trashed() ? $this->indexer->forget($patient) : $this->indexer->reindex($patient);
    }

    public function deleted(Patient $patient): void
    {
        $this->indexer->forget($patient);
    }

    public function restored(Patient $patient): void
    {
        $this->indexer->reindex($patient);
    }
}
