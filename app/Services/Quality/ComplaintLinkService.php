<?php

namespace App\Services\Quality;

use App\Models\Crm\CrmComplaint;
use App\Models\Quality\QaComplaintLink;
use App\Models\Quality\QaIncident;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Validation\ValidationException;

/** A patient complaint can be raised as a quality incident. The complaint stays in the patient-relations module; here only its number is shown and the two are linked. */
class ComplaintLinkService
{
    private const CATEGORY = ['clinical_care' => 'procedure', 'staff_behaviour' => 'behaviour', 'waiting_time' => 'communication', 'billing' => 'documentation', 'cleanliness' => 'environment', 'facility' => 'equipment', 'privacy' => 'communication'];

    public function __construct(private readonly IncidentService $incidents, private readonly AuditLogger $audit) {}

    public function raise(CrmComplaint $complaint, User $by, ?string $harm = null): QaIncident
    {
        $org = (int) $complaint->organization_id;
        if (QaComplaintLink::query()->where(['organization_id' => $org, 'complaint_id' => $complaint->id])->whereNotNull('incident_id')->exists()) {
            throw ValidationException::withMessages(['complaint' => __('This complaint is already linked to an incident.')]);
        }
        $category = self::CATEGORY[$complaint->category] ?? 'other';
        if (! in_array($category, (array) config('quality.incident.categories'), true)) {
            $category = 'other';
        }
        $incident = $this->incidents->report($org, ['kind' => 'incident', 'category' => $category, 'harm' => $harm ?: 'none', 'occurred_at' => $complaint->created_at->setTimezone((string) config('quality.timezone'))->toDateTimeString(),
            'description' => __('Raised from patient complaint :n (:c). The complaint itself stays with patient relations.', ['n' => $complaint->number, 'c' => str_replace('_', ' ', (string) $complaint->category)]),
            'patient_id' => $complaint->patient_id, 'branch_id' => $complaint->branch_id, 'source_type' => 'crm_complaint', 'source_id' => $complaint->id], $by);
        QaComplaintLink::create(['organization_id' => $org, 'complaint_id' => $complaint->id, 'incident_id' => $incident->id, 'created_by' => $by->id]);
        $this->audit->log('CREATE', QaComplaintLink::class, $incident->id, null, ['complaint' => $complaint->number]);

        return $incident;
    }

    /** Complaint numbers linked to an incident. @return list<string> */
    public function numbers(QaIncident $incident): array
    {
        $ids = QaComplaintLink::query()->where('incident_id', $incident->id)->pluck('complaint_id');

        return CrmComplaint::query()->whereIn('id', $ids)->pluck('number')->all();
    }
}
