<?php

namespace App\Services\Facility;

use App\Services\SettingsService;

/** The hospital's facility rules: Settings (`facility.<key>`, dotted) first, then config/facility.php. */
class FacilityPolicy
{
    public function __construct(private readonly SettingsService $settings) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->settings->get("facility.$key");

        return $value !== null && $value !== '' ? $value : config("facility.$key", $default);
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
}
