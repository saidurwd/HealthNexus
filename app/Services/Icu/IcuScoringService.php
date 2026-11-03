<?php

namespace App\Services\Icu;

use App\Events\Icu\ICUClinicalScoreCalculated;
use App\Models\Icu\IcuClinicalScoreResult;
use App\Models\Icu\IcuEpisode;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Validation\ValidationException;

/**
 * Score definitions (variables, bands) live in the icu.scoring_definitions Settings JSON key —
 * never hardcoded in a controller (spec §25). Only the calculated result is persisted, versioned
 * and immutable.
 */
class IcuScoringService
{
    public function __construct(private readonly SettingsService $settings) {}

    public function calculate(IcuEpisode $episode, string $scoreCode, array $inputs, User $user): IcuClinicalScoreResult
    {
        $definitions = $this->settings->get('icu.scoring_definitions', []);
        $definition = $definitions[$scoreCode] ?? null;

        if (! $definition) {
            throw ValidationException::withMessages(['score_code' => "No scoring definition is configured for '{$scoreCode}'."]);
        }

        foreach (array_keys($definition['variables'] ?? []) as $variable) {
            if (! array_key_exists($variable, $inputs)) {
                throw ValidationException::withMessages(['inputs' => "Missing required input '{$variable}' for score '{$scoreCode}'."]);
            }
        }

        $resultValue = array_sum(array_intersect_key($inputs, $definition['variables'] ?? []));
        $interpretation = $this->interpret($resultValue, $definition['bands'] ?? []);

        $result = IcuClinicalScoreResult::create([
            'organization_id' => $episode->organization_id,
            'branch_id' => $episode->branch_id,
            'episode_id' => $episode->id,
            'score_code' => $scoreCode,
            'score_version' => $definition['version'] ?? 1,
            'input_values' => $inputs,
            'result_value' => $resultValue,
            'interpretation' => $interpretation,
            'calculated_by' => $user->id,
            'calculated_at' => now(),
        ]);

        event(new ICUClinicalScoreCalculated($result));

        return $result;
    }

    private function interpret(float $value, array $bands): ?string
    {
        foreach ($bands as $band) {
            if ($value >= ($band['min'] ?? -INF) && $value <= ($band['max'] ?? INF)) {
                return $band['interpretation'] ?? null;
            }
        }

        return null;
    }
}
