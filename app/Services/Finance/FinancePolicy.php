<?php

namespace App\Services\Finance;

use App\Services\SettingsService;

/** The hospital's accounting rules: Settings (`finance.<key>`, dotted) first, then config/finance.php. */
class FinancePolicy
{
    public function __construct(private readonly SettingsService $settings) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->settings->get("finance.$key");

        return $value !== null && $value !== '' ? $value : config("finance.$key", $default);
    }

    public function int(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    public function bool(string $key, bool $default = false): bool
    {
        return filter_var($this->get($key, $default), FILTER_VALIDATE_BOOLEAN);
    }
}
