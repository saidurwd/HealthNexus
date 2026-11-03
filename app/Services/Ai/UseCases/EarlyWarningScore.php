<?php

namespace App\Services\Ai\UseCases;

use App\Models\Ai\AiOutput;
use App\Models\Patient;
use App\Models\User;
use App\Services\Ai\AiCall;
use App\Services\Ai\AiGateway;
use App\Services\Ai\AiPolicy;
use App\Services\Ai\ContextEngine;
use App\Services\Clinical\Observations\ObservationReader;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/**
 * Use case: an early-warning screen. The formula is the published NEWS2 chart (SpO2 scale 1), written out below and shown in the output with every input, its points and what could not be scored. Level of consciousness and
 * supplemental oxygen are not stored with the vital signs, so they are never scored and the total is always "at least". It is a screen, not a diagnosis, and it starts nothing: no alert, no escalation, no order.
 */
class EarlyWarningScore
{
    /** @var array<string, string> catalogue key => label */
    private const PARAMETERS = ['respiratory_rate' => 'Respiratory rate', 'oxygen_saturation' => 'Oxygen saturation', 'systolic_bp' => 'Systolic blood pressure', 'heart_rate' => 'Pulse', 'body_temperature' => 'Temperature'];

    public function __construct(private readonly ContextEngine $context, private readonly ObservationReader $observations, private readonly AiGateway $gateway, private readonly AiPolicy $policy) {}

    public function score(User $user, Patient $patient): AiOutput
    {
        $this->context->authorise($user, $patient);
        $org = (int) $patient->organization_id;
        $tz = (string) config('ai.timezone', 'Asia/Dhaka');
        $oldest = CarbonImmutable::now()->subHours($this->policy->int('risk.max_age_hours', 24));
        $latest = $this->observations->latestVitals($org, (int) $patient->id);
        $items = $scored = $missing = [];
        $total = $highest = 0;
        foreach (self::PARAMETERS as $key => $label) {
            $row = $latest[$key] ?? null;
            if (! $row || ! is_numeric($row->value) || CarbonImmutable::parse((string) $row->effective_at)->lt($oldest)) {
                $missing[] = $key;
                $items[] = ['ref' => 'news:missing:'.$key, 'text' => __(':p: not recorded in the last :h hours, so it could not be scored.', ['p' => __($label), 'h' => $this->policy->int('risk.max_age_hours', 24)]), 'section' => __('Not scored'), 'kind' => 'risk', 'patient_id' => (int) $patient->id];

                continue;
            }
            $value = (float) $row->value;
            if ($key === 'body_temperature' && $value > 45) {
                $value = round(($value - 32) * 5 / 9, 1); // a value stored in Fahrenheit
            }
            $points = $this->points($key, $value);
            $total += $points;
            $highest = max($highest, $points);
            $scored[] = $key;
            $items[] = ['ref' => 'obs:'.$key.':'.$row->source_record_id, 'text' => __(':p :v (recorded :d): :n points.', ['p' => __($label), 'v' => rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.'), 'd' => CarbonImmutable::parse((string) $row->effective_at)->setTimezone($tz)->format('d M Y H:i'), 'n' => $points]), 'section' => __('Scored'), 'kind' => 'risk', 'patient_id' => (int) $patient->id];
        }
        if (count($scored) < $this->policy->int('risk.min_parameters', 3)) {
            throw ValidationException::withMessages(['patient_no' => __('Not enough recent vital signs to score.')]);
        }
        $items[] = ['ref' => 'news:unscored', 'text' => __('Level of consciousness and use of extra oxygen are not stored with the vital signs, so they were not scored.'), 'section' => __('Not scored'), 'kind' => 'risk', 'patient_id' => (int) $patient->id];
        $items[] = ['ref' => 'news:total', 'text' => __('Total from the :n scored parameters: :t points (at least, because two parameters are not scored).', ['n' => count($scored), 't' => $total]), 'section' => __('Total'), 'kind' => 'risk', 'patient_id' => (int) $patient->id];
        $items[] = ['ref' => 'news:band', 'text' => $this->band($total, $highest), 'section' => __('Total'), 'kind' => 'risk', 'patient_id' => (int) $patient->id];

        return $this->gateway->run(new AiCall($org, $user, 'risk_scoring', 'risk_score', $items, 'risk_score', __('Early-warning screen (NEWS2)'), (int) $patient->id, app()->getLocale() === 'bn' ? 'bn' : 'en',
            __('A partial NEWS2 screen from the latest stored vital signs. The true score can be higher. It is not a diagnosis and nothing is started by it.')));
    }

    /** NEWS2, SpO2 scale 1. */
    public function points(string $key, float $v): int
    {
        return match ($key) {
            'respiratory_rate' => $v <= 8 ? 3 : ($v <= 11 ? 1 : ($v <= 20 ? 0 : ($v <= 24 ? 2 : 3))),
            'oxygen_saturation' => $v <= 91 ? 3 : ($v <= 93 ? 2 : ($v <= 95 ? 1 : 0)),
            'systolic_bp' => $v <= 90 ? 3 : ($v <= 100 ? 2 : ($v <= 110 ? 1 : ($v <= 219 ? 0 : 3))),
            'heart_rate' => $v <= 40 ? 3 : ($v <= 50 ? 1 : ($v <= 90 ? 0 : ($v <= 110 ? 1 : ($v <= 130 ? 2 : 3)))),
            'body_temperature' => $v <= 35.0 ? 3 : ($v <= 36.0 ? 1 : ($v <= 38.0 ? 0 : ($v <= 39.0 ? 1 : 2))),
            default => 0,
        };
    }

    private function band(int $total, int $highest): string
    {
        $band = $total >= 7 ? __('high') : ($total >= 5 ? __('medium') : ($highest >= 3 ? __('low-medium (one parameter scores 3)') : __('low')));

        return __('On the NEWS2 chart a total of :t falls in the :b band.', ['t' => $total, 'b' => $band]);
    }
}
