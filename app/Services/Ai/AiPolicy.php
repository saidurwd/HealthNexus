<?php

namespace App\Services\Ai;

use App\Services\SettingsService;

/** The hospital's AI rules: Settings (`ai.<key>`, dotted) first, then config/ai.php. */
class AiPolicy
{
    public function __construct(private readonly SettingsService $settings) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->settings->get("ai.$key");

        return $value !== null && $value !== '' ? $value : config("ai.$key", $default);
    }

    public function int(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    public function bool(string $key, bool $default = false): bool
    {
        return filter_var($this->get($key, $default), FILTER_VALIDATE_BOOLEAN);
    }

    /** @return list<string> */
    public function list(string $key): array
    {
        return array_values((array) $this->get($key, []));
    }

    public function float(string $key, float $default = 0.0): float
    {
        return (float) $this->get($key, $default);
    }
}
