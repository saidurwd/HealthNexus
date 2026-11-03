<?php

namespace App\Services\Hr;

use App\Services\SettingsService;

/** The hospital's HR and payroll rules: Settings (`hr.<key>`) first, then config/hr.php. Nothing here is a legal fact; see config/hr.php. */
class HrPolicy
{
    public function __construct(private readonly SettingsService $settings) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->settings->get("hr.$key");

        return $value !== null && $value !== '' ? $value : config("hr.$key", $default);
    }

    /** A nested config value that a setting may override as a whole, e.g. ('roster', 'min_rest_hours'). */
    public function nested(string $key, string $inner, mixed $default = null): mixed
    {
        $value = $this->get($key);

        return is_array($value) && array_key_exists($inner, $value) ? $value[$inner] : $default;
    }
}
