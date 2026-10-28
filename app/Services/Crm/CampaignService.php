<?php

namespace App\Services\Crm;

use App\Models\Crm\CrmCampaign;
use App\Models\Crm\CrmCampaignRecipient;
use App\Models\Crm\CrmSegment;
use App\Models\Patient;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Portal\CommunicationService;
use App\Services\Portal\PhoneNumber;
use App\Services\Portal\PortalException;
use App\Services\Portal\PortalPolicy;
use Illuminate\Validation\ValidationException;

/**
 * Sending to a group (S11). A second person approves before anything is sent. Every recipient goes through the same consent, opt-out, quiet-hours and weekly-cap rules as any
 * other message, and the campaign keeps one row per patient saying what happened — so "who did we not message, and why" has an answer.
 */
class CampaignService
{
    public function __construct(private readonly SegmentService $segments, private readonly CommunicationService $comms, private readonly PortalPolicy $policy, private readonly AuditLogger $audit) {}

    public function create(int $organizationId, User $by, array $data): CrmCampaign
    {
        $segment = CrmSegment::query()->where('organization_id', $organizationId)->findOrFail($data['segment_id']);
        if (! in_array($data['purpose'], ['service', 'marketing'], true) || ! in_array($data['channel'], (array) $this->policy->get('communication.channels', []), true)) {
            throw ValidationException::withMessages(['channel' => __('Choose a channel and a purpose.')]);
        }
        $campaign = CrmCampaign::create(['organization_id' => $organizationId, 'name' => mb_substr($data['name'], 0, 120), 'purpose' => $data['purpose'], 'channel' => $data['channel'], 'segment_id' => $segment->id,
            'subject' => $data['subject'] ?? null, 'body' => $data['body'], 'status' => 'draft', 'scheduled_at' => $data['scheduled_at'] ?? null, 'created_by' => $by->id]);
        $this->audit->log('CREATE', CrmCampaign::class, $campaign->id, null, ['segment' => $segment->id, 'purpose' => $data['purpose']]);

        return $campaign;
    }

    public function submit(CrmCampaign $campaign): CrmCampaign
    {
        $this->expect($campaign, ['draft']);
        if ($campaign->segment->status !== 'active') {
            throw new PortalException(__('The group has not been approved yet.'), 409);
        }
        $campaign->update(['status' => 'pending']);

        return $campaign;
    }

    public function approve(CrmCampaign $campaign, User $by): CrmCampaign
    {
        $this->expect($campaign, ['pending']);
        if ($campaign->created_by === $by->id) {
            throw new PortalException(__('A campaign must be approved by someone other than the person who wrote it.'), 403);
        }
        $campaign->update(['status' => 'approved', 'approved_by' => $by->id, 'approved_at' => now()]);
        $this->audit->log('UPDATE', CrmCampaign::class, $campaign->id, null, ['status' => 'approved', 'by' => $by->id]);

        return $campaign;
    }

    public function cancel(CrmCampaign $campaign): CrmCampaign
    {
        $this->expect($campaign, ['draft', 'pending', 'approved']);
        $campaign->update(['status' => 'cancelled']);

        return $campaign;
    }

    /** Sends (or defers, or suppresses) to every patient in the group. Safe to run twice: a patient is only ever recorded once per campaign. */
    public function run(CrmCampaign $campaign): CrmCampaign
    {
        $this->expect($campaign, ['approved', 'running']);
        if ($campaign->scheduled_at && $campaign->scheduled_at->isFuture()) {
            throw new PortalException(__('This campaign is scheduled for later.'), 409);
        }
        $campaign->update(['status' => 'running', 'started_at' => $campaign->started_at ?? now()]);

        $this->segments->patients($campaign->segment)->orderBy('patients.id')->chunkById(200, function ($patients) use ($campaign) {
            foreach ($patients as $patient) {
                $this->sendTo($campaign, $patient);
            }
        }, 'patients.id', 'id');

        $stats = CrmCampaignRecipient::query()->where('campaign_id', $campaign->id)->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status')->all();
        $campaign->update(['status' => 'completed', 'completed_at' => now(), 'stats' => $stats]);
        $this->audit->log('UPDATE', CrmCampaign::class, $campaign->id, null, ['status' => 'completed', 'stats' => $stats]);

        return $campaign;
    }

    private function sendTo(CrmCampaign $campaign, Patient $patient): void
    {
        if (CrmCampaignRecipient::query()->where(['campaign_id' => $campaign->id, 'patient_id' => $patient->id])->exists()) {
            return;
        }
        $to = $campaign->channel === 'sms' ? PhoneNumber::normalize($patient->phone) : ($campaign->channel === 'email' ? $patient->email : null);
        $name = trim((string) ($patient->preferred_name ?: $patient->first_name));
        $row = $this->comms->send(['organization_id' => $campaign->organization_id, 'patient_id' => $patient->id, 'channel' => $campaign->channel, 'purpose' => $campaign->purpose, 'template' => 'campaign',
            'to' => $to, 'subject' => $campaign->subject, 'body' => str_replace('{name}', $name, $campaign->body), 'campaign_id' => $campaign->id]);
        CrmCampaignRecipient::create(['campaign_id' => $campaign->id, 'patient_id' => $patient->id, 'status' => match ($row->status) {
            'sent' => 'sent', 'deferred' => 'deferred', 'suppressed' => 'suppressed', 'failed' => 'failed', default => 'queued',
        }, 'reason' => $row->reason, 'outbox_id' => $row->id]);
    }

    /** @param list<string> $states */
    private function expect(CrmCampaign $campaign, array $states): void
    {
        if (! in_array($campaign->status, $states, true)) {
            throw new PortalException(__('This campaign is :s and cannot do that.', ['s' => $campaign->status]), 409);
        }
    }
}
