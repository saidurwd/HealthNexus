<?php

namespace App\Services\Interop;

use App\Models\Integration\IntegrationAttempt;
use App\Models\Integration\IntegrationMessage;
use App\Models\Integration\IntegrationPartner;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Clinical\Alerts\AlertRequest;
use App\Services\Clinical\Alerts\AlertService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * The life of an integration message (S02): queued → sending → processed, or retrying with back-off, or dead after the last try, then replayable by a person.
 * The same (hospital, direction, key) is stored once, so a retried delivery from either side never does the work twice.
 */
class MessageService
{
    public function __construct(private readonly InteropPolicy $policy, private readonly AuditLogger $audit, private readonly AlertService $alerts) {}

    /**
     * @param  array{endpoint_id?:int|null,patient_id?:int|null,event_key?:string|null,correlation_id?:string|null,provenance?:array|null}  $o
     * @return array{0:IntegrationMessage,1:bool} the message and whether it already existed
     */
    public function enqueue(int $organizationId, ?IntegrationPartner $partner, string $channel, string $type, string $payload, string $idempotencyKey, array $o = []): array
    {
        return $this->store($organizationId, 'out', 'queued', $partner, $channel, $type, $payload, $idempotencyKey, $o + ['next_attempt_at' => now()]);
    }

    /** @return array{0:IntegrationMessage,1:bool} */
    public function receive(int $organizationId, ?IntegrationPartner $partner, string $channel, string $type, string $payload, string $idempotencyKey, array $o = []): array
    {
        return $this->store($organizationId, 'in', 'received', $partner, $channel, $type, $payload, $idempotencyKey, $o);
    }

    private function store(int $organizationId, string $direction, string $status, ?IntegrationPartner $partner, string $channel, string $type, string $payload, string $key, array $o): array
    {
        $key = mb_substr(($partner ? $partner->id.':' : '').$key, 0, 190);
        $existing = IntegrationMessage::query()->where(['organization_id' => $organizationId, 'direction' => $direction, 'idempotency_key' => $key])->first();
        if ($existing) {
            return [$existing, true];
        }
        try {
            $m = IntegrationMessage::create(['organization_id' => $organizationId, 'partner_id' => $partner?->id, 'endpoint_id' => $o['endpoint_id'] ?? null, 'direction' => $direction, 'channel' => $channel, 'message_type' => mb_substr($type, 0, 40),
                'event_key' => $o['event_key'] ?? null, 'patient_id' => $o['patient_id'] ?? null, 'idempotency_key' => $key, 'status' => $status, 'attempts' => 0, 'next_attempt_at' => $o['next_attempt_at'] ?? null, 'payload' => $payload,
                'payload_hash' => hash('sha256', $payload), 'correlation_id' => $o['correlation_id'] ?? (string) Str::ulid(), 'provenance' => $o['provenance'] ?? null]);
        } catch (QueryException) { // two requests raced: the second finds the first's row
            return [IntegrationMessage::query()->where(['organization_id' => $organizationId, 'direction' => $direction, 'idempotency_key' => $key])->firstOrFail(), true];
        }

        return [$m, false];
    }

    /** Takes up to $limit due outbound messages for sending. */
    public function claim(int $limit): array
    {
        return DB::transaction(function () use ($limit) {
            $rows = IntegrationMessage::query()->where('direction', 'out')->whereIn('status', ['queued', 'retrying'])->where('next_attempt_at', '<=', now())->orderBy('next_attempt_at')->orderBy('id')->limit($limit)->lockForUpdate()->get();
            foreach ($rows as $row) {
                $row->update(['status' => 'sending']);
            }

            return $rows->all();
        });
    }

    public function succeeded(IntegrationMessage $m, int $attempt, ?int $http, string $detail, ?string $response): void
    {
        $this->attempt($m, $attempt, 'ok', $http, $detail);
        $m->update(['status' => 'processed', 'attempts' => $attempt, 'processed_at' => now(), 'next_attempt_at' => null, 'response' => $response, 'error' => null]);
    }

    public function failed(IntegrationMessage $m, int $attempt, bool $retryable, ?int $http, string $detail, ?string $response = null): void
    {
        $max = $this->policy->int('delivery.max_attempts', 6);
        $final = ! $retryable || $attempt >= $max;
        $this->attempt($m, $attempt, $final ? 'fail' : 'retry', $http, $detail);
        if ($final) {
            $m->update(['status' => 'dead', 'attempts' => $attempt, 'dead_at' => now(), 'next_attempt_at' => null, 'error' => mb_substr($detail, 0, 500), 'response' => $response]);
            $this->raiseDeadLetterAlert($m);

            return;
        }
        $backoff = (array) $this->policy->get('delivery.backoff_seconds', [30, 120, 600]);
        $wait = (int) ($backoff[min($attempt - 1, count($backoff) - 1)] ?? 600);
        $m->update(['status' => 'retrying', 'attempts' => $attempt, 'next_attempt_at' => now()->addSeconds($wait), 'error' => mb_substr($detail, 0, 500), 'response' => $response]);
    }

    private function attempt(IntegrationMessage $m, int $no, string $outcome, ?int $http, string $detail): void
    {
        IntegrationAttempt::create(['message_id' => $m->id, 'attempt_no' => $no, 'outcome' => $outcome, 'http_status' => $http, 'detail' => mb_substr($detail, 0, 500), 'started_at' => now(), 'finished_at' => now()]);
    }

    private function raiseDeadLetterAlert(IntegrationMessage $m): void
    {
        try {
            $this->alerts->raise(new AlertRequest(organizationId: $m->organization_id, category: 'integration', severity: 'warning', message: "Message {$m->message_type} to/from an outside system failed for good and is in the dead-letter list.",
                sourceModule: 'interop', patientId: $m->patient_id, sourceType: 'integration_message', sourceId: $m->id, dedupKey: 'integration-dead-'.$m->partner_id, ackRequired: false));
        } catch (Throwable) {
            // an alert must never stop the queue
        }
    }

    /** A person sends a dead (or any finished) message again as a new message that remembers where it came from. */
    public function replay(IntegrationMessage $m, User $by): IntegrationMessage
    {
        if (! in_array($m->status, ['dead', 'processed', 'dismissed', 'rejected'], true)) {
            throw new InteropException('Only a finished or failed message can be sent again.', 409);
        }
        $n = IntegrationMessage::query()->where('replay_of_id', $m->id)->count() + 1;
        $copy = IntegrationMessage::create(['organization_id' => $m->organization_id, 'partner_id' => $m->partner_id, 'endpoint_id' => $m->endpoint_id, 'direction' => $m->direction, 'channel' => $m->channel, 'message_type' => $m->message_type,
            'event_key' => $m->event_key, 'patient_id' => $m->patient_id, 'idempotency_key' => mb_substr($m->idempotency_key.':replay:'.$n, 0, 190), 'status' => $m->direction === 'out' ? 'queued' : 'received', 'attempts' => 0,
            'next_attempt_at' => $m->direction === 'out' ? now() : null, 'payload' => $m->payload, 'payload_hash' => $m->payload_hash, 'correlation_id' => $m->correlation_id, 'replay_of_id' => $m->id,
            'provenance' => ($m->provenance ?? []) + ['replayed_by' => $by->id, 'replayed_at' => now()->toIso8601String()]]);
        $this->audit->log('CREATE', IntegrationMessage::class, $copy->id, null, ['replay_of' => $m->id, 'by' => $by->id]);

        return $copy;
    }

    public function dismiss(IntegrationMessage $m, User $by, string $reason): IntegrationMessage
    {
        if ($m->status !== 'dead') {
            throw new InteropException('Only a message in the dead-letter list can be set aside.', 409);
        }
        $m->update(['status' => 'dismissed', 'error' => mb_substr('Set aside by a person: '.$reason, 0, 500)]);
        $this->audit->log('UPDATE', IntegrationMessage::class, $m->id, ['status' => 'dead'], ['status' => 'dismissed', 'by' => $by->id, 'reason' => $reason]);

        return $m;
    }

    /** Messages stuck in "sending" (a worker died) go back to the queue. */
    public function releaseStuck(int $minutes = 10): int
    {
        return IntegrationMessage::query()->where('status', 'sending')->where('updated_at', '<', now()->subMinutes($minutes))->update(['status' => 'retrying', 'next_attempt_at' => now()]);
    }
}
