<?php

namespace App\Services\Watch;

use App\Services\SettingsService;

/** The hospital's rules for disease watch: Settings (`watch.<key>`) first, then config/watch.php. */
class WatchPolicy
{
    public function __construct(private readonly SettingsService $settings) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->settings->get("watch.$key");

        return $value !== null && $value !== '' ? $value : config("watch.$key", $default);
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
