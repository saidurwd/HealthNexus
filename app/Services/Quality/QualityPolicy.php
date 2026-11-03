<?php

namespace App\Services\Quality;

use App\Services\SettingsService;

/** The hospital's quality rules: Settings (`quality.<key>`, dotted) first, then config/quality.php. */
class QualityPolicy
{
    public function __construct(private readonly SettingsService $settings) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->settings->get("quality.$key");

        return $value !== null && $value !== '' ? $value : config("quality.$key", $default);
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

    /** Harm levels in order, lowest first. */
    public function harmRank(string $harm): int
    {
        $i = array_search($harm, $this->list('incident.harm'), true);

        return $i === false ? 0 : (int) $i;
    }
}
