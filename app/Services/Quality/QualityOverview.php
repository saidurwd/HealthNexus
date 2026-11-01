<?php

namespace App\Services\Quality;

use App\Models\Quality\QaAction;
use App\Models\Quality\QaComplianceItem;
use App\Models\Quality\QaDocument;
use App\Models\Quality\QaDocumentVersion;
use App\Models\Quality\QaIncident;
use App\Models\Quality\QaIndicator;
use App\Models\Quality\QaIndicatorValue;
use App\Models\Quality\QaIsolation;
use App\Models\Quality\QaMortalityReview;
use App\Models\Quality\QaOutbreak;
use App\Models\Quality\QaPrivilege;
use App\Models\User;
use Carbon\CarbonImmutable;

/** The figures on the quality home page and in the quality report. What a person sees follows their branches; nothing here names a reporter. */
class QualityOverview
{
    public function __construct(private readonly QualityScope $scope, private readonly QualityPolicy $policy, private readonly IndicatorService $indicators) {}

    /** @return array<string,mixed> */
    public function summary(User $user, int $organizationId, int $months = 6): array
    {
        $tz = (string) config('quality.timezone', 'Asia/Dhaka');
        $from = CarbonImmutable::now($tz)->startOfMonth()->subMonths($months - 1);
        $incidents = fn () => $this->scope->apply(QaIncident::query(), $user, $organizationId);
        $actions = fn () => $this->scope->apply(QaAction::query(), $user, $organizationId);
        $today = today()->toDateString();

        $byMonth = $incidents()->where('occurred_at', '>=', $from->utc())->get(['occurred_at', 'kind'])->groupBy(fn ($i) => $i->occurred_at->copy()->setTimezone($tz)->format('Y-m'))
            ->map(fn ($g) => ['all' => $g->count(), 'near_miss' => $g->where('kind', 'near_miss')->count()]);
        $trend = [];
        for ($i = 0; $i < $months; $i++) {
            $key = $from->addMonths($i)->format('Y-m');
            $trend[] = ['month' => $key, 'all' => $byMonth[$key]['all'] ?? 0, 'near_miss' => $byMonth[$key]['near_miss'] ?? 0];
        }

        return [
            'incidents_open' => $incidents()->where('status', '!=', 'closed')->count(),
            'incidents_by_status' => $incidents()->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status')->all(),
            'incidents_overdue' => $incidents()->where('status', '!=', 'closed')->where('due_on', '<', $today)->count(),
            'serious_90d' => $incidents()->where('occurred_at', '>=', now()->subDays(90))->where(fn ($q) => $q->where('kind', 'sentinel')->orWhereIn('harm', ['major', 'catastrophic']))->count(),
            'by_category' => $incidents()->where('occurred_at', '>=', $from->utc())->selectRaw('category, count(*) as n')->groupBy('category')->orderByDesc('n')->pluck('n', 'category')->all(),
            'trend' => $trend,
            'actions_open' => $actions()->whereIn('status', ['open', 'in_progress'])->count(),
            'actions_overdue' => $actions()->whereIn('status', ['open', 'in_progress'])->where('due_on', '<', $today)->count(),
            'actions_to_verify' => $actions()->where('status', 'done')->count(),
            'outbreaks_open' => $this->scope->apply(QaOutbreak::query(), $user, $organizationId)->where('status', '!=', 'closed')->count(),
            'isolations_active' => $this->scope->apply(QaIsolation::query(), $user, $organizationId)->whereNull('ended_at')->count(),
            'deaths_to_review' => $this->scope->apply(QaMortalityReview::query(), $user, $organizationId)->where('status', 'pending')->count(),
            'documents_in_review' => QaDocumentVersion::query()->where('organization_id', $organizationId)->where('status', 'in_review')->count(),
            'documents_due' => QaDocument::query()->where('organization_id', $organizationId)->where('status', 'active')->whereHas('current', fn ($q) => $q->where('review_due_on', '<=', today()->addDays($this->policy->int('document.review_warning_days', 30))->toDateString()))->count(),
            'privileges_waiting' => QaPrivilege::query()->where('organization_id', $organizationId)->where('status', 'requested')->count(),
            'compliance_late' => QaComplianceItem::query()->where('organization_id', $organizationId)->where('status', 'active')->where('due_on', '<=', today()->addDays($this->policy->int('compliance.warning_days', 60))->toDateString())->count(),
            'indicators' => $this->latestIndicators($organizationId),
        ];
    }

    /** @return list<array{indicator:QaIndicator,value:?float,previous:?float,on_target:?bool,period:?string}> */
    public function latestIndicators(int $organizationId): array
    {
        $out = [];
        foreach ($this->indicators->forOrganization($organizationId) as $i) {
            $values = QaIndicatorValue::query()->where(['organization_id' => $organizationId, 'indicator_id' => $i->id])->orderByDesc('period')->limit(6)->get();
            // the last month with a value, not necessarily this month
            $latest = $values->first(fn ($v) => $v->value !== null);
            $prev = $latest ? $values->first(fn ($v) => $v->value !== null && $v->period->lt($latest->period)) : null;
            $out[] = ['indicator' => $i, 'value' => $latest ? (float) $latest->value : null, 'previous' => $prev ? (float) $prev->value : null, 'on_target' => $this->indicators->onTarget($i, $latest ? (float) $latest->value : null),
                'period' => $latest?->period->format('M Y'), 'series' => $values->sortBy('period')->map(fn ($v) => $v->value === null ? null : (float) $v->value)->values()->all()];
        }

        return $out;
    }
}
