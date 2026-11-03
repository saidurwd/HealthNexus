<?php

namespace App\Interop\Transports;

use App\Contracts\Integration\NationalExchangeGateway;
use App\Models\Integration\IntegrationEndpoint;
use App\Models\Integration\IntegrationMessage;
use App\Models\Integration\IntegrationPartner;

/** Hands a record to the national exchange through its gateway (the null adapter until DGHS onboarding, the sandbox test client before that). */
class NationalTransport implements Transport
{
    public function __construct(private readonly NationalExchangeGateway $gateway) {}

    public function send(IntegrationMessage $message, IntegrationEndpoint $endpoint, IntegrationPartner $partner): TransportResult
    {
        $record = json_decode((string) $message->payload, true);
        if (! is_array($record)) {
            return TransportResult::refused('payload is not a record');
        }
        $result = $this->gateway->publishRecord($record);

        return $result->handled ? TransportResult::ok(null, 'accepted by the national exchange', json_encode($result->data)) : TransportResult::retry($result->reason);
    }
}
