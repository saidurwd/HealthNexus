<?php

namespace App\Contracts\Integration;

/** Text messages to patients (Phase 17). Callers depend on this interface only; a Null adapter is bound until the hospital chooses a provider (guideline 13.4). Module key: portal. */
interface SmsGateway
{
    public function send(string $to, string $body, array $options = []): IntegrationResult;
}
