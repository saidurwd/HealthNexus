<?php

namespace App\Interop;

use App\Models\Integration\IntegrationNonce;
use App\Models\Integration\IntegrationPartner;
use App\Services\Interop\CredentialService;
use Illuminate\Database\QueryException;

/**
 * Signed webhooks with replay protection (S10). The signature covers the timestamp AND the body (`HMAC-SHA256(secret, "<timestamp>.<body>")`), so a captured request cannot be sent again later
 * (the timestamp must be recent) or twice (its delivery id is remembered), and cannot be altered (the body is signed).
 */
class WebhookSigner
{
    public function __construct(private readonly CredentialService $credentials) {}

    public function sign(string $body, string $secret, ?int $timestamp = null): array
    {
        $timestamp ??= time();

        return [config('interop.webhook.timestamp_header') => (string) $timestamp, config('interop.webhook.signature_header') => 'sha256='.hash_hmac('sha256', $timestamp.'.'.$body, $secret)];
    }

    /** @return array{ok:bool,reason:?string} */
    public function verify(IntegrationPartner $partner, string $body, ?string $timestamp, ?string $signature, ?string $deliveryId): array
    {
        if (! ctype_digit((string) $timestamp) || $signature === null || $deliveryId === null || $deliveryId === '') {
            return ['ok' => false, 'reason' => 'missing signature, timestamp or delivery id'];
        }
        if (abs(time() - (int) $timestamp) > (int) config('interop.webhook.tolerance_seconds', 300)) {
            return ['ok' => false, 'reason' => 'timestamp too old or too far ahead'];
        }
        $valid = false;
        foreach ($this->credentials->usable($partner, 'webhook-signing') as $cred) {
            $valid = hash_equals('sha256='.hash_hmac('sha256', $timestamp.'.'.$body, (string) $cred->secret), $signature) || $valid;
        }
        if (! $valid) {
            return ['ok' => false, 'reason' => 'signature does not match'];
        }
        try {
            IntegrationNonce::create(['partner_id' => $partner->id, 'nonce' => mb_substr($deliveryId, 0, 120), 'seen_at' => now()]);
        } catch (QueryException) {
            return ['ok' => false, 'reason' => 'this delivery was already received'];
        }

        return ['ok' => true, 'reason' => null];
    }
}
