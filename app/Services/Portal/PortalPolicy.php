<?php

namespace App\Services\Portal;

use App\Services\SettingsService;

/** The hospital's portal rules: Settings (`portal.<key>`, dotted) first, then config/portal.php. Nothing here is a legal fact; see config/portal.php. */
class PortalPolicy
{
    public function __construct(private readonly SettingsService $settings) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->settings->get("portal.$key");

        return $value !== null && $value !== '' ? $value : config("portal.$key", $default);
    }

    public function int(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }
}
