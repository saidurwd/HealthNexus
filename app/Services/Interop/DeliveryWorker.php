<?php

namespace App\Services\Interop;

use App\Interop\Transports\HttpsTransport;
use App\Interop\Transports\MllpTransport;
use App\Interop\Transports\NationalTransport;
use App\Interop\Transports\Transport;
use App\Interop\Transports\TransportResult;
use App\Models\Integration\IntegrationEndpoint;
use App\Models\Integration\IntegrationMessage;
use App\Models\Integration\IntegrationPartner;
use Throwable;

/** The sender. Runs from `integration:work` (a separate process on a schedule or a supervisor), never inside a web request. */
class DeliveryWorker
{
    /** @var array<string, Transport> */
    private array $transports = [];

    public function __construct(private readonly MessageService $messages, private readonly InteropPolicy $policy) {}

    /** Replace a transport (tests, or a hospital-specific adapter). */
    public function useTransport(string $name, Transport $transport): void
    {
        $this->transports[$name] = $transport;
    }

    private function transport(string $name): ?Transport
    {
        return $this->transports[$name] ??= match ($name) {
            'https' => app(HttpsTransport::class), 'mllp' => app(MllpTransport::class), 'national' => app(NationalTransport::class), default => null,
        };
    }

    /** @return array{sent:int,retry:int,dead:int} */
    public function run(?int $limit = null): array
    {
        $out = ['sent' => 0, 'retry' => 0, 'dead' => 0];
        foreach ($this->messages->claim($limit ?? $this->policy->int('delivery.batch', 50)) as $message) {
            $result = $this->deliver($message);
            $attempt = $message->attempts + 1;
            if ($result->ok) {
                $this->messages->succeeded($message, $attempt, $result->status, $result->detail, $result->response);
                $out['sent']++;
            } else {
                $this->messages->failed($message, $attempt, $result->retryable, $result->status, $result->detail, $result->response);
                $out[$message->fresh()->status === 'dead' ? 'dead' : 'retry']++;
            }
        }

        return $out;
    }

    private function deliver(IntegrationMessage $message): TransportResult
    {
        try {
            $partner = $message->partner_id ? IntegrationPartner::query()->find($message->partner_id) : null;
            if ($partner === null || $partner->status !== 'active') {
                return TransportResult::retry('the partner is not active');
            }
            $endpoint = $message->endpoint_id ? IntegrationEndpoint::query()->find($message->endpoint_id) : IntegrationEndpoint::query()->where(['partner_id' => $partner->id, 'active' => true])->orderBy('id')->first();
            if ($endpoint === null || ! $endpoint->active) {
                return TransportResult::refused('the partner has no active endpoint');
            }
            $transport = $this->transport($endpoint->transport);

            return $transport === null ? TransportResult::refused("no way to send by {$endpoint->transport}") : $transport->send($message, $endpoint, $partner);
        } catch (Throwable $e) {
            return TransportResult::retry('unexpected error: '.mb_substr($e->getMessage(), 0, 300));
        }
    }
}
