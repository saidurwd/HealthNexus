<?php

namespace App\Services\Dashboard;

use App\Services\SettingsService;

/**
 * The targets a hospital sets for its figures (Settings `dashboard.target.<code>`, for example `dashboard.target.ed_door_to_doctor` = 15). No target ships with the software: a figure without one is shown plainly,
 * with no good or bad colour.
 */
class Targets
{
    public function __construct(private readonly SettingsService $settings) {}

    public function get(string $code): ?float
    {
        $v = $this->settings->get("dashboard.target.$code");

        return $v === null || $v === '' || ! is_numeric($v) ? null : (float) $v;
    }
}
