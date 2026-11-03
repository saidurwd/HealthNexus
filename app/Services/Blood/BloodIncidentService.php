<?php

namespace App\Services\Blood;

use App\Models\Blood\BloodIncident;
use App\Models\Blood\BloodUnit;
use App\Models\Patient;
use App\Models\User;
use App\Services\Clinical\Alerts\AlertRequest;
use App\Services\Clinical\Alerts\AlertService;
use Illuminate\Validation\ValidationException;

/** Incidents and near-misses: reactions, wrong samples, overridden checks, emergency releases, recalls. Closing needs a written resolution. */
class BloodIncidentService
{
    public function __construct(private readonly AlertService $alerts, private readonly BloodTestingService $testing) {}

    public function open(int $organizationId, string $type, string $description, string $severity = 'moderate', ?Patient $patient = null, ?BloodUnit $unit = null, ?object $ref = null, ?User $by = null, bool $alert = true): BloodIncident
    {
        $incident = BloodIncident::create([
            'organization_id' => $organizationId, 'patient_id' => $patient?->id, 'unit_id' => $unit?->id, 'incident_type' => $type, 'severity' => $severity, 'description' => $description,
            'ref_type' => $ref ? $ref::class : null, 'ref_id' => $ref?->getKey(), 'occurred_at' => now(), 'reported_by' => $by?->id,
        ]);

        if ($alert && in_array($severity, ['high', 'critical'], true)) {
            $this->alerts->raise(new AlertRequest(
                organizationId: $organizationId, category: 'blood_incident', severity: $severity === 'critical' ? 'critical' : 'warning', sourceModule: 'bloodbank', patientId: $patient?->id,
                message: "Blood bank incident ($type): $description", sourceType: BloodIncident::class, sourceId: $incident->id,
                recipientUserIds: $this->testing->roleUsers($organizationId), dedupKey: "blood-incident:{$incident->id}", raisedBy: $by?->id,
            ));
        }

        return $incident;
    }

    public function close(BloodIncident $incident, string $resolution, User $by): BloodIncident
    {
        if ($incident->status === 'closed') {
            throw ValidationException::withMessages(['incident' => 'This incident is already closed.']);
        }
        if (strlen(trim($resolution)) < 5) {
            throw ValidationException::withMessages(['resolution' => 'Say what was found and what was done.']);
        }
        $incident->update(['status' => 'closed', 'resolution' => $resolution, 'closed_at' => now(), 'closed_by' => $by->id]);

        return $incident->refresh();
    }
}
