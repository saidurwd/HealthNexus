<?php

namespace App\Interop\Transports;

use App\Interop\WebhookSigner;
use App\Models\Integration\IntegrationEndpoint;
use App\Models\Integration\IntegrationMessage;
use App\Models\Integration\IntegrationPartner;
use App\Services\Interop\CredentialService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/** POSTs a message to a partner's URL. A bearer / API-key credential labelled `outbound-api` is attached; webhooks (format json) are also signed with `webhook-signing`. */
class HttpsTransport implements Transport
{
    public function __construct(private readonly CredentialService $credentials, private readonly WebhookSigner $signer) {}

    public function send(IntegrationMessage $message, IntegrationEndpoint $endpoint, IntegrationPartner $partner): TransportResult
    {
        $body = (string) $message->payload;
        $headers = ['Content-Type' => $endpoint->format === 'fhir' ? 'application/fhir+json' : 'application/json', config('interop.webhook.delivery_header') => $message->public_id, 'X-Request-Id' => $message->correlation_id ?? $message->public_id];
        if ($api = $this->credentials->current($partner, 'outbound-api')) {
            $headers['Authorization'] = $api->kind === 'basic' ? 'Basic '.base64_encode((string) $api->secret) : 'Bearer '.$api->secret;
        }
        if ($endpoint->format === 'json' && ($sign = $this->credentials->current($partner, 'webhook-signing'))) {
            $headers += $this->signer->sign($body, (string) $sign->secret);
        }
        try {
            $response = Http::timeout($endpoint->timeout_seconds ?: 10)->withHeaders($headers)->withBody($body, $headers['Content-Type'])->post($endpoint->url);
        } catch (ConnectionException $e) {
            return TransportResult::retry('could not connect: '.mb_substr($e->getMessage(), 0, 200));
        }
        $status = $response->status();
        $text = mb_substr($response->body(), 0, 2000);
        if ($response->successful()) {
            return TransportResult::ok($status, "HTTP $status", $text);
        }
        if ($status >= 500 || in_array($status, [408, 425, 429], true)) {
            return TransportResult::retry("HTTP $status", $status, $text);
        }

        return TransportResult::refused("HTTP $status", $status, $text);
    }
}
