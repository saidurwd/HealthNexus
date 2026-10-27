<?php

namespace App\Services\Inventory;

use App\Services\SettingsService;

/** The hospital's inventory and procurement rules: Settings (`inventory.<key>`) first, then config/inventory.php. */
class InventoryPolicy
{
    public function __construct(private readonly SettingsService $settings) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->settings->get("inventory.$key");

        return $value !== null && $value !== '' ? $value : config("inventory.$key", $default);
    }

    /** @return list<array{level: int, from: int|float, permission: string, label: string}> the levels that must approve a document of this amount */
    public function approvalLevels(string $amount): array
    {
        return array_values(array_filter((array) $this->get('approval_levels'), fn ($l) => bccomp((string) $l['from'], $amount, 2) <= 0));
    }
}
