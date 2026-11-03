<?php

namespace App\Contracts\Integration;

/** Secure video consultation rooms (Phase 17 abstraction; the real workflow is Phase 24). Module key: portal. */
interface TelehealthGateway
{
    /** @param array{appointment_public_id:string,starts_at:string,duration_minutes:int,participants:list<string>} $request */
    public function createRoom(array $request): IntegrationResult;

    public function closeRoom(string $roomRef): IntegrationResult;
}
