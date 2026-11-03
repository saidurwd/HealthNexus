<?php

namespace App\Services\Crm;

use App\Models\Crm\CrmInteraction;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Portal\PortalPolicy;
use Illuminate\Validation\ValidationException;

/** The log of contacts with patients and enquirers — calls, walk-ins, messages — and who must follow up (S11). */
class InteractionService
{
    public function __construct(private readonly PortalPolicy $policy, private readonly AuditLogger $audit) {}

    /** @param  User|null  $by  the staff member who logged it; null when the system opens a follow-up by itself (a low rating) */
    public function log(int $organizationId, ?User $by, array $d): CrmInteraction
    {
        if (! in_array($d['channel'] ?? '', (array) $this->policy->get('crm.interaction_channels', []), true)) {
            throw ValidationException::withMessages(['channel' => __('Choose a channel.')]);
        }
        $row = CrmInteraction::create(['organization_id' => $organizationId, 'branch_id' => $d['branch_id'] ?? null, 'patient_id' => $d['patient_id'] ?? null, 'contact_name' => $d['contact_name'] ?? null, 'contact_phone' => $d['contact_phone'] ?? null,
            'channel' => $d['channel'], 'direction' => ($d['direction'] ?? 'in') === 'out' ? 'out' : 'in', 'subject' => mb_substr((string) $d['subject'], 0, 160), 'summary' => $d['summary'] ?? null, 'outcome' => $d['outcome'] ?? null,
            'status' => ! empty($d['follow_up_on']) ? 'open' : ($d['status'] ?? 'done'), 'assigned_to' => $d['assigned_to'] ?? (($d['follow_up_on'] ?? null) ? $by?->id : null), 'follow_up_on' => $d['follow_up_on'] ?? null, 'created_by' => $by?->id]);
        $this->audit->log('CREATE', CrmInteraction::class, $row->id, null, ['channel' => $row->channel, 'patient_id' => $row->patient_id]);

        return $row;
    }

    public function assign(CrmInteraction $row, ?User $to): CrmInteraction
    {
        $row->update(['assigned_to' => $to?->id, 'status' => 'open']);

        return $row;
    }

    public function complete(CrmInteraction $row, ?string $outcome): CrmInteraction
    {
        $row->update(['status' => 'done', 'outcome' => $outcome ?: $row->outcome]);

        return $row;
    }
}
