<?php

namespace App\Services\Portal;

use App\Models\Patient;
use App\Models\PatientPortalAccount;
use App\Models\Portal\PortalMessage;
use App\Models\Portal\PortalNotification;
use App\Models\Portal\PortalThread;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

/**
 * Secure messages between a patient and the hospital (S09). A message is words, not an order: nothing here creates a prescription, a test or an appointment. Text that looks
 * like an emergency is flagged for staff and the sender is told to call the emergency number — the portal is never the way to get urgent help.
 */
class MessagingService
{
    public function __construct(private readonly PortalPolicy $policy, private readonly AuditLogger $audit) {}

    public function looksUrgent(string $text): bool
    {
        $t = mb_strtolower($text);
        foreach ((array) $this->policy->get('messaging.urgent_words', []) as $word) {
            if ($word !== '' && str_contains($t, mb_strtolower($word))) {
                return true;
            }
        }

        return false;
    }

    public function start(PatientPortalAccount $account, Patient $patient, string $subject, string $category, string $body): PortalThread
    {
        $body = $this->clean($body);
        if ($body === '') {
            throw new PortalException(__('Please write your message.'));
        }
        $category = in_array($category, (array) $this->policy->get('messaging.categories', []), true) ? $category : 'general';
        $urgent = $this->looksUrgent($subject.' '.$body);

        return DB::transaction(function () use ($account, $patient, $subject, $category, $body, $urgent) {
            $thread = PortalThread::create(['organization_id' => $patient->organization_id, 'patient_id' => $patient->id, 'account_id' => $account->id, 'subject' => mb_substr(strip_tags($subject), 0, 160) ?: __('Message'), 'category' => $category,
                'status' => 'open', 'urgent' => $urgent, 'reply_due_at' => now()->addHours($urgent ? 2 : $this->policy->int('messaging.reply_due_hours', 48)), 'last_message_at' => now()]);
            PortalMessage::create(['thread_id' => $thread->id, 'sender' => 'patient', 'account_id' => $account->id, 'body' => $body, 'created_at' => now()]);
            if ($urgent) {
                PortalMessage::create(['thread_id' => $thread->id, 'sender' => 'system', 'body' => __('If this is an emergency, call :n or go to the nearest emergency department now. Messages here are not watched all the time.', ['n' => $this->policy->get('messaging.emergency_number', '999')]), 'created_at' => now()]);
            }
            $this->audit->log('CREATE', PortalThread::class, $thread->id, null, ['urgent' => $urgent, 'category' => $category]);

            return $thread;
        });
    }

    public function patientReply(PatientPortalAccount $account, PortalThread $thread, string $body): PortalMessage
    {
        $body = $this->clean($body);
        if ($body === '') {
            throw new PortalException(__('Please write your message.'));
        }
        if ($thread->status === 'closed') {
            throw new PortalException(__('This conversation is closed. Please start a new message.'), 409);
        }
        $urgent = $thread->urgent || $this->looksUrgent($body);
        $thread->update(['status' => 'open', 'urgent' => $urgent, 'last_message_at' => now(), 'reply_due_at' => now()->addHours($urgent ? 2 : $this->policy->int('messaging.reply_due_hours', 48))]);

        return PortalMessage::create(['thread_id' => $thread->id, 'sender' => 'patient', 'account_id' => $account->id, 'body' => $body, 'created_at' => now()]);
    }

    public function staffReply(PortalThread $thread, User $by, string $body): PortalMessage
    {
        $body = $this->clean($body);
        if ($body === '') {
            throw new PortalException(__('Please write your message.'));
        }
        $message = PortalMessage::create(['thread_id' => $thread->id, 'sender' => 'staff', 'user_id' => $by->id, 'body' => $body, 'created_at' => now()]);
        $thread->update(['status' => 'answered', 'last_message_at' => now(), 'assigned_to' => $thread->assigned_to ?: $by->id, 'reply_due_at' => null]);
        PortalNotification::create(['organization_id' => $thread->organization_id, 'patient_id' => $thread->patient_id, 'kind' => 'message', 'title' => __('You have a reply'), 'body' => (string) $thread->subject, 'link' => '/portal/messages/'.$thread->public_id]);
        $this->audit->log('UPDATE', PortalThread::class, $thread->id, null, ['reply_by' => $by->id]);

        return $message;
    }

    public function close(PortalThread $thread): PortalThread
    {
        $thread->update(['status' => 'closed', 'closed_at' => now(), 'reply_due_at' => null]);

        return $thread;
    }

    public function markRead(PortalThread $thread, string $reader): void
    {
        PortalMessage::query()->where('thread_id', $thread->id)->whereNull('read_at')->where('sender', '!=', $reader)->update(['read_at' => now()]);
    }

    private function clean(string $body): string
    {
        return trim(mb_substr(strip_tags($body), 0, 4000));
    }
}
