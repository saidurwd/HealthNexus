<?php

namespace App\Services\Blood;

use App\Services\SettingsService;

/** The hospital's blood-bank rules: Settings (`blood.<key>`) first, then config/blood.php. Nothing clinical is hard-coded in a service. */
class BloodPolicy
{
    public function __construct(private readonly SettingsService $settings) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->settings->get("blood.$key");

        return $value !== null && $value !== '' ? $value : config("blood.$key", $default);
    }

    /** @return array{label: string, shelf_days: int, temp: array{0: int|float, 1: int|float}, storage: string, kind: string, volume: int, charge: string} */
    public function component(string $key): array
    {
        return $this->get('components')[$key] ?? throw new \InvalidArgumentException("Unknown blood component [$key].");
    }

    /** @return list<string> donor ABO groups whose cells (red) or plasma may be given to a recipient group */
    public function acceptableDonorGroups(string $recipientAbo, string $kind): array
    {
        $map = $kind === 'plasma' ? $this->get('plasma_compatible') : $this->get('red_cell_compatible');

        return $map[$recipientAbo] ?? [];
    }
}
