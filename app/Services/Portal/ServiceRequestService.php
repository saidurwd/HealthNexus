<?php

namespace App\Services\Portal;

use App\Models\Patient;
use App\Models\PatientPortalAccount;
use App\Models\Portal\PortalNotification;
use App\Models\Portal\PortalServiceRequest;
use App\Models\User;
use App\Services\AuditLogger;

/** Things a patient asks the hospital to do that are not a visit: a copy of records, a reprint, a change of details (S09). Staff do the work; nothing changes by itself. */
class ServiceRequestService
{
    public function __construct(private readonly PortalPolicy $policy, private readonly AuditLogger $audit) {}

    public function create(PatientPortalAccount $account, Patient $patient, string $kind, ?string $details): PortalServiceRequest
    {
        if (! in_array($kind, (array) $this->policy->get('service_requests.kinds', []), true)) {
            throw new PortalException(__('Choose what you need.'));
        }
        $row = PortalServiceRequest::create(['organization_id' => $patient->organization_id, 'patient_id' => $patient->id, 'account_id' => $account->id, 'kind' => $kind, 'details' => $details ? mb_substr(strip_tags($details), 0, 2000) : null,
            'status' => 'requested', 'due_on' => now()->addDays($this->policy->int('service_requests.due_days', 5))->toDateString()]);
        $this->audit->log('CREATE', PortalServiceRequest::class, $row->id, null, ['kind' => $kind]);

        return $row;
    }

    public function update(PortalServiceRequest $row, User $by, string $status, ?string $resolution): PortalServiceRequest
    {
        if (! in_array($status, ['in_progress', 'done', 'rejected'], true)) {
            throw new PortalException(__('Unknown status.'));
        }
        if (in_array($row->status, ['done', 'rejected'], true)) {
            throw new PortalException(__('This request is already closed.'), 409);
        }
        $row->update(['status' => $status, 'assigned_to' => $row->assigned_to ?: $by->id, 'resolution' => $resolution ? mb_substr($resolution, 0, 300) : $row->resolution, 'closed_at' => in_array($status, ['done', 'rejected'], true) ? now() : null]);
        $this->audit->log('UPDATE', PortalServiceRequest::class, $row->id, null, ['status' => $status, 'by' => $by->id]);
        if ($status !== 'in_progress') {
            PortalNotification::create(['organization_id' => $row->organization_id, 'patient_id' => $row->patient_id, 'kind' => 'announcement', 'title' => __('Your request was answered'), 'body' => (string) ($row->resolution ?: $status), 'link' => '/portal/requests']);
        }

        return $row;
    }
}
