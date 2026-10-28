<?php

namespace App\Contracts\Integration;

/**
 * Online payment aggregator: bKash, Nagad, card (Phase 17). `initiate` returns `gateway_ref` and `redirect_url` in the result data; `verify` asks the aggregator what really happened
 * to a payment (the browser coming back is never proof): `status` is succeeded | failed | pending and `amount` is what was taken. Module key: portal.
 */
interface PaymentGateway
{
    public function name(): string;

    /** @param array{amount:string,currency:string,method:string,reference:string,idempotency_key:string,return_url:string} $request */
    public function initiate(array $request): IntegrationResult;

    public function verify(string $gatewayRef): IntegrationResult;
}
