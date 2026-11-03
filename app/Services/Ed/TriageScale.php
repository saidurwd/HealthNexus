<?php

namespace App\Services\Ed;

use App\Services\SettingsService;
use Illuminate\Validation\ValidationException;

/** The triage scale is a SETTING, not code (guideline 26.1 override 8): ESI, MTS, CTAS or a local scale can be configured per hospital. */
class TriageScale
{
    public function __construct(private readonly SettingsService $settings) {}

    /** @return array<int, array{name: string, color: string, door_to_doctor: int, reassess: int}> level => definition, lowest number = most urgent */
    public function levels(): array
    {
        $configured = $this->settings->get('ed.triage_scale');
        $configured = is_string($configured) ? json_decode($configured, true) : $configured;

        $levels = is_array($configured) && $configured !== [] ? $configured : config('ed.triage_scale');
        $levels = collect($levels)->mapWithKeys(fn ($v, $k) => [(int) $k => $v])->sortKeys()->all();

        return $levels;
    }

    public function level(int $level): array
    {
        return $this->levels()[$level] ?? throw ValidationException::withMessages(['level' => "Triage level [$level] is not on this hospital's scale."]);
    }

    public function mostUrgent(): int
    {
        return (int) array_key_first($this->levels());
    }

    public function doorToTriageMinutes(): int
    {
        return (int) ($this->settings->get('ed.door_to_triage_minutes') ?: config('ed.door_to_triage_minutes'));
    }
}
