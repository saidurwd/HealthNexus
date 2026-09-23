<?php

namespace App\Services\Patients;

use App\Models\Patient;
use App\Models\PatientTimelineEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Event-sourced patient timeline: each significant patient-record action writes a row here
 * instead of the page live-aggregating every related table on each view. Replaces the previous
 * PatientService::getTimeline() live-query approach.
 */
class PatientTimelineService
{
    public function record(
        Patient $patient,
        string $eventType,
        string $description,
        ?Model $subject = null,
        ?User $actor = null,
        array $metadata = [],
    ): PatientTimelineEvent {
        return PatientTimelineEvent::create([
            'company_id' => $patient->company_id,
            'patient_id' => $patient->id,
            'event_type' => $eventType,
            'description' => $description,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'actor_id' => $actor?->id ?? auth()->id(),
            'metadata' => $metadata,
            'event_at' => now(),
        ]);
    }

    public function for(Patient $patient): Collection
    {
        return $patient->timelineEvents()->with('actor')->orderByDesc('event_at')->get();
    }
}
