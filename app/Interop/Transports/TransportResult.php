<?php

namespace App\Interop\Transports;

/** What happened when we tried to hand a message over. `retryable` false means trying again cannot help (the other side refused it for good). */
final class TransportResult
{
    private function __construct(public readonly bool $ok, public readonly bool $retryable, public readonly ?int $status, public readonly string $detail, public readonly ?string $response = null) {}

    public static function ok(?int $status = null, string $detail = 'delivered', ?string $response = null): self
    {
        return new self(true, false, $status, $detail, $response);
    }

    public static function retry(string $detail, ?int $status = null, ?string $response = null): self
    {
        return new self(false, true, $status, $detail, $response);
    }

    public static function refused(string $detail, ?int $status = null, ?string $response = null): self
    {
        return new self(false, false, $status, $detail, $response);
    }
}
