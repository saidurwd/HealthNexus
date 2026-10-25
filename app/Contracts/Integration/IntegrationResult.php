<?php

namespace App\Contracts\Integration;

/** Outcome of a call through a forward-integration gateway (guideline 13.4). */
final class IntegrationResult
{
    private function __construct(
        public readonly bool $handled,
        public readonly string $reason,
        public readonly array $data = [],
    ) {}

    public static function handled(array $data = []): self
    {
        return new self(true, 'ok', $data);
    }

    /** The module behind the gateway is not built or not enabled yet. */
    public static function notEnabled(string $gateway): self
    {
        return new self(false, "{$gateway}: module not enabled");
    }
}
