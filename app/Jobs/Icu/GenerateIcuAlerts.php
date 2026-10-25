<?php

namespace App\Jobs\Icu;

use App\Models\Icu\IcuEpisode;
use App\Models\Icu\IcuObservation;
use App\Services\Icu\IcuAlertService;
use App\Services\SettingsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Scans recent icu_observations against the configurable icu.alert_threshold_rules Settings
 * JSON key — no synchronous domain event fires per individual observation (Phase 11 plan
 * decision #9); this scheduled scan is the sole alert-detection path.
 */
class GenerateIcuAlerts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(SettingsService $settings, IcuAlertService $alerts): void
    {
        $rules = $settings->get('icu.alert_threshold_rules', []);

        if (empty($rules)) {
            return;
        }

        $episodes = IcuEpisode::query()->whereNull('discharged_at')->get();

        foreach ($episodes as $episode) {
            foreach ($rules as $observationType => $rule) {
                $latest = IcuObservation::where('episode_id', $episode->id)
                    ->where('observation_type', $observationType)
                    ->whereNotNull('value_numeric')
                    ->latest('observed_at')
                    ->first();

                if (! $latest) {
                    continue;
                }

                $breached = ($rule['low'] ?? null) !== null && $latest->value_numeric < $rule['low']
                    ? "below {$rule['low']}"
                    : ((($rule['high'] ?? null) !== null && $latest->value_numeric > $rule['high']) ? "above {$rule['high']}" : null);

                if (! $breached) {
                    continue;
                }

                $alreadyAlerted = $episode->criticalAlerts()
                    ->where('rule', $observationType)
                    ->where('source_type', $latest->getMorphClass())
                    ->where('source_id', $latest->id)
                    ->exists();

                if ($alreadyAlerted) {
                    continue;
                }

                $alerts->trigger(
                    $episode,
                    $observationType,
                    $rule['severity'] ?? 'moderate',
                    (string) $latest->value_numeric,
                    $breached,
                    $latest,
                );
            }
        }
    }
}
