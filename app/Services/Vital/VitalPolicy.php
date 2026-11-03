<?php

namespace App\Services\Vital;

use App\Services\SettingsService;

/** The hospital's rules for birth, death and medico-legal records: Settings (`vital.<key>`) first, then config/vital.php. */
class VitalPolicy
{
    public function __construct(private readonly SettingsService $settings) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->settings->get("vital.$key");

        return $value !== null && $value !== '' ? $value : config("vital.$key", $default);
    }

    public function int(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    /** @return list<string> */
    public function list(string $key): array
    {
        $v = $this->get($key, []);

        return array_values(is_array($v) ? array_map('strval', $v) : array_filter(array_map('trim', explode(',', (string) $v))));
    }
}
