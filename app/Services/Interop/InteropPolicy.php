<?php

namespace App\Services\Interop;

use App\Services\SettingsService;

/** The hospital's integration rules: Settings (`interop.<key>`, dotted) first, then config/interop.php. */
class InteropPolicy
{
    public function __construct(private readonly SettingsService $settings) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->settings->get("interop.$key");

        return $value !== null && $value !== '' ? $value : config("interop.$key", $default);
    }

    public function int(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }
}
