<?php

namespace App\Services\Welfare;

use App\Services\SettingsService;

/** The hospital's welfare rules: Settings (`welfare.<key>`, dotted) first, then config/welfare.php. */
class WelfarePolicy
{
    public function __construct(private readonly SettingsService $settings) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->settings->get("welfare.$key");

        return $value !== null && $value !== '' ? $value : config("welfare.$key", $default);
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
