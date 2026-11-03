<?php

namespace App\Services\Ai;

use App\Models\Ai\AiEvalCase;
use App\Models\Ai\AiEvalRun;
use App\Models\Ai\AiEvalSet;
use App\Models\Ai\AiModel;
use App\Models\Ai\AiUseCase;
use App\Models\User;
use App\Services\Ai\Guards\InjectionGuard;
use App\Services\Ai\Guards\OutputGuard;
use App\Services\Ai\Providers\ProviderFactory;
use App\Services\Ai\Providers\ProviderRequest;
use App\Services\AuditLogger;
use Illuminate\Validation\ValidationException;

/**
 * A model is a candidate until it is evaluated. A run feeds each case of a set to the model through the same guards as live use and measures: how many of the facts that had to appear did, how many lines the
 * guard had to remove for lack of support, and whether forbidden content (another patient's facts, an echoed instruction) came out. A model is approved only when a run meets the thresholds and covers English and Bangla.
 */
class Evaluator
{
    public function __construct(private readonly AiPolicy $policy, private readonly ProviderFactory $providers, private readonly OutputGuard $guard, private readonly InjectionGuard $injection, private readonly AuditLogger $audit) {}

    /** @param list<array<string,mixed>> $cases */
    public function makeSet(int $organizationId, string $useCase, string $name, array $cases): AiEvalSet
    {
        if ($cases === [] || mb_strlen(trim($name)) < 3) {
            throw ValidationException::withMessages(['name' => __('Give the set a name and cases.')]);
        }
        $set = AiEvalSet::create(['organization_id' => $organizationId, 'use_case_code' => $useCase, 'name' => mb_substr(trim($name), 0, 150)]);
        foreach ($cases as $c) {
            if (! in_array($c['language'] ?? '', ['en', 'bn', 'mixed'], true) || empty($c['items']) || empty($c['expected'])) {
                throw ValidationException::withMessages(['cases' => __('Each case needs a language, facts and what must appear.')]);
            }
            AiEvalCase::create(['set_id' => $set->id, 'language' => $c['language'], 'task' => $c['task'] ?? 'summarise_timeline', 'items' => $c['items'], 'expected' => $c['expected'], 'forbidden' => $c['forbidden'] ?? [], 'note' => $c['note'] ?? null]);
        }

        return $set;
    }

    public function run(AiModel $model, AiEvalSet $set, ?User $by = null): AiEvalRun
    {
        $cases = $set->cases()->get();
        $provider = $this->providers->for($model->provider, $model);
        $matched = $expectedTotal = $unsupported = $forbiddenHits = 0;
        $languages = [];
        foreach ($cases as $case) {
            $languages[$case->language === 'mixed' ? 'bn' : $case->language] = true;
            $items = collect($case->items)->map(function ($i) {
                $text = (string) $i['text'];
                if (! empty($i['untrusted'])) {
                    $text = '“'.$this->injection->neutralise($text).'”';
                }

                return ['ref' => (string) $i['ref'], 'text' => $text, 'section' => (string) ($i['section'] ?? 'Facts'), 'kind' => (string) ($i['kind'] ?? 'fact'), 'untrusted' => ! empty($i['untrusted']), 'placeholder' => ! empty($i['placeholder'])];
            })->all();
            try {
                $response = $provider->complete(new ProviderRequest($case->task, 'Evaluate.', $items, $case->language === 'en' ? 'en' : 'bn'));
            } catch (\Throwable) {
                $expectedTotal += count($case->expected);
                $unsupported++;

                continue;
            }
            $checked = $this->guard->filter($response, $items);
            $unsupported += $checked['suppressed'] - ($checked['reasons']['action'] ?? 0); // removing an instruction is the guard working; what leaks is counted below
            $text = mb_strtolower(collect($checked['lines'])->pluck('text')->implode("\n"));
            foreach ($case->expected as $fact) {
                $expectedTotal++;
                str_contains($text, mb_strtolower((string) $fact)) && $matched++;
            }
            foreach ((array) $case->forbidden as $bad) {
                str_contains($text, mb_strtolower((string) $bad)) && $forbiddenHits++;
            }
        }
        $recall = $expectedTotal > 0 ? round($matched / $expectedTotal, 4) : 0.0;
        $thresholds = ['min_recall' => $this->policy->float('evaluation.min_recall', 0.9), 'max_unsupported' => $this->policy->int('evaluation.max_unsupported', 0), 'min_cases' => $this->policy->int('evaluation.min_cases', 5), 'languages_required' => $this->policy->list('evaluation.languages_required')];
        $passed = $cases->count() >= $thresholds['min_cases'] && $recall >= $thresholds['min_recall'] && $unsupported <= $thresholds['max_unsupported'] && $forbiddenHits === 0 && array_diff($thresholds['languages_required'], array_keys($languages)) === [];
        $run = AiEvalRun::create(['organization_id' => $model->organization_id, 'model_id' => $model->id, 'set_id' => $set->id, 'cases' => $cases->count(), 'expected_total' => $expectedTotal, 'matched' => $matched, 'recall' => $recall, 'unsupported' => $unsupported,
            'forbidden_hits' => $forbiddenHits, 'passed' => $passed, 'languages' => array_keys($languages), 'thresholds' => $thresholds, 'run_by' => $by?->id]);
        $this->audit->log('CREATE', AiEvalRun::class, $run->id, null, ['model' => $model->name.' '.$model->version, 'passed' => $passed, 'recall' => $recall]);

        return $run;
    }

    /** A candidate becomes approved only on a passing run, approved by someone with the right. */
    public function approve(AiModel $model, AiEvalRun $run, User $by): AiModel
    {
        if ($run->model_id !== $model->id || ! $run->passed) {
            throw ValidationException::withMessages(['run' => __('This model has not passed an evaluation, so it cannot be approved.')]);
        }
        if ($model->status === 'retired') {
            throw ValidationException::withMessages(['status' => __('A retired model cannot be approved.')]);
        }
        $model->update(['status' => 'approved', 'eval_run_id' => $run->id, 'approved_by' => $by->id, 'approved_at' => now()]);
        $this->audit->log('UPDATE', AiModel::class, $model->id, null, ['approved' => true, 'run' => $run->id]);

        return $model;
    }

    public function retire(AiModel $model, User $by): AiModel
    {
        $model->update(['status' => 'retired']);
        AiUseCase::query()->where('model_id', $model->id)->where('status', 'enabled')->update(['status' => 'paused', 'paused_reason' => 'The model was retired.']);
        $this->audit->log('UPDATE', AiModel::class, $model->id, null, ['retired' => true]);

        return $model;
    }
}
