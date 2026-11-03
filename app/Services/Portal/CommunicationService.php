<?php

namespace App\Services\Portal;

use App\Contracts\Integration\SmsGateway;
use App\Models\Patient;
use App\Models\Portal\PortalCommPreference;
use App\Models\Portal\PortalNotification;
use App\Models\Portal\PortalOutbox;
use App\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Every message to a patient goes through here (guideline override 7): the patient's choice per channel and purpose, quiet hours, a weekly cap on marketing, and an
 * outbox row that says what happened and why. `transactional` (codes, booking, results ready, receipts) cannot be switched off; `service` is on until switched off;
 * `marketing` is off until the patient says yes.
 */
class CommunicationService
{
    public function __construct(private readonly SmsGateway $sms, private readonly PortalPolicy $policy, private readonly AuditLogger $audit) {}

    /**
     * @param  array{organization_id:int,patient_id?:int|null,channel:string,purpose:string,template:string,to?:string|null,subject?:string|null,body:string,campaign_id?:int|null}  $m
     */
    public function send(array $m): PortalOutbox
    {
        $row = PortalOutbox::create([
            'organization_id' => $m['organization_id'], 'patient_id' => $m['patient_id'] ?? null, 'channel' => $m['channel'], 'purpose' => $m['purpose'],
            'template' => $m['template'], 'to' => $m['to'] ?? null, 'subject' => $m['subject'] ?? null, 'body' => $m['body'], 'campaign_id' => $m['campaign_id'] ?? null, 'status' => 'queued',
        ]);

        $why = $this->blockedReason($row);
        if ($why !== null) {
            $row->update(['status' => 'suppressed', 'reason' => $why]);

            return $row;
        }

        $at = $this->deferUntil($row);
        if ($at !== null) {
            $row->update(['status' => 'deferred', 'send_after' => $at, 'reason' => 'quiet hours']);

            return $row;
        }

        return $this->deliver($row);
    }

    /** Sends what was held back for quiet hours, checking the patient's choice again at the moment of sending. */
    public function releaseDeferred(?CarbonInterface $now = null): int
    {
        $count = 0;
        PortalOutbox::query()->where('status', 'deferred')->where('send_after', '<=', $now ?? now())->orderBy('id')->each(function (PortalOutbox $row) use (&$count) {
            $why = $this->blockedReason($row);
            if ($why !== null) {
                $row->update(['status' => 'suppressed', 'reason' => $why]);

                return;
            }
            $this->deliver($row);
            $count++;
        });

        return $count;
    }

    private function deliver(PortalOutbox $row): PortalOutbox
    {
        if ($row->channel === 'inapp') {
            if ($row->patient_id !== null) {
                PortalNotification::create(['organization_id' => $row->organization_id, 'patient_id' => $row->patient_id, 'kind' => $this->kindFor($row->template), 'title' => (string) ($row->subject ?: mb_substr($row->body, 0, 80)), 'body' => mb_substr($row->body, 0, 400)]);
            }
            $row->update(['status' => 'sent', 'sent_at' => now(), 'reason' => null]);

            return $row;
        }

        if ($row->channel === 'sms') {
            $result = $row->to ? $this->sms->send($row->to, $row->body, ['template' => $row->template]) : null;
            if ($result?->handled) {
                $row->update(['status' => 'sent', 'sent_at' => now(), 'reason' => null]);
            } else {
                $row->update(['status' => 'queued', 'reason' => $row->to ? 'no SMS provider installed' : 'no phone number']);
            }

            return $row;
        }

        // Email and push: kept in the outbox until an adapter is installed.
        $row->update(['status' => 'queued', 'reason' => "no {$row->channel} adapter installed"]);

        return $row;
    }

    private function kindFor(string $template): string
    {
        return match (true) {
            str_starts_with($template, 'appointment') => 'appointment',
            str_starts_with($template, 'result') => 'result',
            str_starts_with($template, 'payment') => 'payment',
            str_starts_with($template, 'refill') => 'prescription',
            str_starts_with($template, 'message') => 'message',
            str_starts_with($template, 'document') => 'document',
            default => 'announcement',
        };
    }

    private function blockedReason(PortalOutbox $row): ?string
    {
        if ($row->purpose === 'transactional' || $row->patient_id === null) {
            return null;
        }
        if (! $this->isOptedIn($row->patient_id, $row->channel, $row->purpose)) {
            $said = PortalCommPreference::query()->where(['patient_id' => $row->patient_id, 'channel' => $row->channel, 'purpose' => $row->purpose])->exists();

            return $row->purpose === 'marketing' && ! $said ? 'no opt-in for marketing' : 'patient opted out';
        }
        if ($row->purpose === 'marketing') {
            $cap = $this->policy->int('communication.max_marketing_per_week', 2);
            $sent = PortalOutbox::query()->where('patient_id', $row->patient_id)->where('purpose', 'marketing')->whereIn('status', ['sent', 'queued'])->where('id', '<>', $row->id)->where('created_at', '>=', now()->subDays(7))->count();
            if ($sent >= $cap) {
                return 'weekly marketing limit reached';
            }
        }

        return null;
    }

    public function isOptedIn(int $patientId, string $channel, string $purpose): bool
    {
        if ($purpose === 'transactional') {
            return true;
        }
        $row = PortalCommPreference::query()->where(['patient_id' => $patientId, 'channel' => $channel, 'purpose' => $purpose])->first();

        return $row ? (bool) $row->opted_in : $purpose === 'service';
    }

    private function deferUntil(PortalOutbox $row): ?CarbonInterface
    {
        if ($row->purpose === 'transactional' || ! in_array($row->channel, ['sms', 'push'], true)) {
            return null;
        }

        return $this->nextAllowed(CarbonImmutable::now());
    }

    /** The moment a quiet-hours message may go; null when it may go now. */
    public function nextAllowed(CarbonInterface $at): ?CarbonImmutable
    {
        $tz = (string) $this->policy->get('communication.timezone', 'Asia/Dhaka');
        $quiet = (array) $this->policy->get('communication.quiet_hours', ['start' => '21:00', 'end' => '08:00']);
        $local = CarbonImmutable::instance($at)->setTimezone($tz);
        [$sh, $sm] = array_map('intval', explode(':', $quiet['start']));
        [$eh, $em] = array_map('intval', explode(':', $quiet['end']));
        $start = $local->setTime($sh, $sm);
        $end = $local->setTime($eh, $em);

        if ($start > $end) { // overnight window
            if ($local >= $start) {
                return $end->addDay()->utc();
            }
            if ($local < $end) {
                return $end->utc();
            }

            return null;
        }

        return $local >= $start && $local < $end ? $end->utc() : null;
    }

    /** @return array<string, array<string, bool>> channel => purpose => effective choice */
    public function matrix(Patient $patient): array
    {
        $out = [];
        foreach ((array) $this->policy->get('communication.channels', []) as $channel) {
            foreach ((array) $this->policy->get('communication.purposes', []) as $purpose) {
                $out[$channel][$purpose] = $this->isOptedIn($patient->id, $channel, $purpose);
            }
        }

        return $out;
    }

    public function setPreference(Patient $patient, string $channel, string $purpose, bool $optedIn, string $source = 'portal'): PortalCommPreference
    {
        if (! in_array($channel, (array) $this->policy->get('communication.channels', []), true) || ! in_array($purpose, (array) $this->policy->get('communication.purposes', []), true)) {
            throw new PortalException(__('Unknown channel or purpose.'));
        }
        if ($purpose === 'transactional' && ! $optedIn) {
            throw new PortalException(__('Messages about your own care and security cannot be switched off.'));
        }
        $pref = PortalCommPreference::query()->updateOrCreate(
            ['patient_id' => $patient->id, 'channel' => $channel, 'purpose' => $purpose],
            ['organization_id' => $patient->organization_id, 'opted_in' => $optedIn, 'source' => $source, 'changed_at' => now()],
        );
        $this->audit->log('UPDATE', PortalCommPreference::class, $pref->id, null, ['patient_id' => $patient->id, 'channel' => $channel, 'purpose' => $purpose, 'opted_in' => $optedIn, 'source' => $source]);

        return $pref;
    }

    /** "STOP": every non-essential message on every channel is switched off at once. */
    public function optOutAll(Patient $patient, string $source = 'stop_word'): void
    {
        foreach ((array) $this->policy->get('communication.channels', []) as $channel) {
            foreach (['service', 'marketing'] as $purpose) {
                $this->setPreference($patient, $channel, $purpose, false, $source);
            }
        }
    }
}
