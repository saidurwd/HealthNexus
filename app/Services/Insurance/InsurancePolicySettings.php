<?php

namespace App\Services\Insurance;

use App\Services\SettingsService;

/** The hospital's insurance rules: Settings (`insurance.<key>`) first, then config/insurance.php. Nothing payer-specific is hard-coded. */
class InsurancePolicySettings
{
    public function __construct(private readonly SettingsService $settings) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->settings->get("insurance.$key");

        return $value !== null && $value !== '' ? $value : config("insurance.$key", $default);
    }
}
