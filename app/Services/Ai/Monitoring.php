<?php

namespace App\Services\Ai;

use App\Models\Ai\AiFeedback;
use App\Models\Ai\AiIncident;
use App\Models\Ai\AiOutput;
use App\Models\Ai\AiRequest;
use App\Models\Ai\AiUseCase;
use Carbon\CarbonImmutable;

/** How each AI function is doing: use, refusals, how often clinicians accept what it drafts, errors they report, lines removed, speed, incidents. A fall in acceptance, compared with the period before, is drift and opens an incident. */
class Monitoring
{
    public function __construct(private readonly AiPolicy $policy, private readonly AiIncidents $incidents) {}

    /** @return array<string,mixed> */
    public function metrics(int $organizationId, string $useCase, ?int $days = null, ?CarbonImmutable $until = null): array
    {
        $days ??= $this->policy->int('monitoring.window_days', 30);
        $to = $until ?? CarbonImmutable::now();
        $from = $to->subDays($days);
        $req = AiRequest::query()->where(['organization_id' => $organizationId, 'use_case_code' => $useCase])->whereBetween('created_at', [$from, $to]);
        $out = AiOutput::query()->where(['organization_id' => $organizationId, 'use_case_code' => $useCase])->whereBetween('created_at', [$from, $to]);
        $accepted = (clone $out)->where('status', 'accepted')->count();
        $rejected = (clone $out)->where('status', 'rejected')->count();
        $reviewed = $accepted + $rejected;
        $outputIds = (clone $out)->pluck('id');

        return ['requests' => (clone $req)->count(), 'completed' => (clone $req)->where('outcome', 'completed')->count(), 'blocked' => (clone $req)->where('outcome', 'blocked')->count(), 'failed' => (clone $req)->where('outcome', 'failed')->count(),
            'outputs' => (clone $out)->count(), 'accepted' => $accepted, 'rejected' => $rejected, 'expired' => (clone $out)->where('status', 'expired')->count(), 'waiting' => (clone $out)->where('status', 'draft')->count(),
            'acceptance' => $reviewed > 0 ? round(100 * $accepted / $reviewed, 1) : null, 'reviewed' => $reviewed, 'suppressed_lines' => (int) (clone $out)->sum('suppressed'),
            'not_useful' => AiFeedback::query()->whereIn('output_id', $outputIds)->where('rating', 'not_useful')->count(), 'errors_reported' => AiFeedback::query()->whereIn('output_id', $outputIds)->whereNotNull('error_type')->count(),
            'avg_latency_ms' => ($l = (clone $req)->where('outcome', 'completed')->avg('latency_ms')) === null ? null : (int) round((float) $l), 'incidents_open' => AiIncident::query()->where(['organization_id' => $organizationId, 'use_case_code' => $useCase])->where('status', '!=', 'closed')->count()];
    }

    /** Compare the last window with the one before it; a fall of the set number of points raises an incident (once per use case per day). @return list<string> use cases with drift */
    public function drift(int $organizationId): array
    {
        $found = [];
        $days = $this->policy->int('monitoring.window_days', 30);
        foreach (AiUseCase::query()->where('organization_id', $organizationId)->where('status', 'enabled')->get() as $uc) {
            $now = $this->metrics($organizationId, $uc->code, $days);
            $before = $this->metrics($organizationId, $uc->code, $days, CarbonImmutable::now()->subDays($days));
            $min = $this->policy->int('monitoring.min_outputs', 10);
            if ($now['acceptance'] === null || $before['acceptance'] === null || $now['reviewed'] < $min || $before['reviewed'] < $min) {
                continue;
            }
            if ($this->policy->int('monitoring.drift_points', 20) <= $before['acceptance'] - $now['acceptance']) {
                $exists = AiIncident::query()->where(['organization_id' => $organizationId, 'use_case_code' => $uc->code, 'kind' => 'drift'])->where('created_at', '>=', now()->startOfDay())->exists();
                $exists || $this->incidents->open($organizationId, 'drift', 'medium', $uc->code, null, __('Acceptance of :u fell from :a% to :b% compared with the period before.', ['u' => $uc->name, 'a' => $before['acceptance'], 'b' => $now['acceptance']]));
                $found[] = $uc->code;
            }
        }

        return $found;
    }
}
