<?php

namespace App\Services\Ai;

use App\Models\Ai\AiModel;
use App\Models\Ai\AiOutput;
use App\Models\Ai\AiProvider;
use App\Models\Ai\AiRequest;
use App\Models\Ai\AiUseCase;
use App\Models\Patient;
use App\Services\Ai\Guards\Deidentifier;
use App\Services\Ai\Guards\InjectionGuard;
use App\Services\Ai\Guards\OutputGuard;
use App\Services\Ai\Providers\ProviderFactory;
use App\Services\Ai\Providers\ProviderRequest;
use Throwable;

/**
 * The one door to any AI provider. Every call goes through the same checks, in the same order, and leaves the same record whether it is allowed, blocked or fails:
 * master switch → use case registered and enabled → the person may use AI → provider enabled and model approved → data class and de-identification → instruction-like text → provider → output guard → draft.
 */
class AiGateway
{
    public function __construct(private readonly AiPolicy $policy, private readonly PromptRegistry $prompts, private readonly ProviderFactory $providers, private readonly InjectionGuard $injection, private readonly Deidentifier $deidentifier,
        private readonly OutputGuard $output, private readonly AiIncidents $incidents) {}

    public function run(AiCall $call): AiOutput
    {
        $uc = AiUseCase::query()->with('provider', 'model')->where(['organization_id' => $call->organizationId, 'code' => $call->useCase])->first();
        $this->check($call, $uc);
        /** @var AiProvider $provider */
        $provider = $uc->provider;
        /** @var AiModel $model */
        $model = $uc->model;
        $mode = $this->mode($provider);
        $level = $call->patientId ? 'C4' : $uc->data_level;
        if ($mode === 'deidentified' && $level === 'C4') {
            $level = 'C3';
        }
        if (strcmp($level, $provider->max_level) > 0) {
            $this->block($call, $uc, $provider, $model, __('The provider is not cleared for data of this class.'));
        }

        $names = [];
        if ($call->patientId) {
            $names = $this->deidentifier->namesOf(Patient::query()->where('organization_id', $call->organizationId)->find($call->patientId));
        }
        $score = 0;
        $items = [];
        foreach ($call->inputs as $input) {
            $score += $this->injection->score((string) $input);
        }
        foreach (array_slice($call->items, 0, (int) $this->policy->int('context.max_items', 80)) as $item) {
            $text = (string) ($item['text'] ?? '');
            if (! empty($item['untrusted'])) {
                $score += $this->injection->score($text);
                $text = $this->injection->neutralise($text, (int) $this->policy->int('context.max_text_chars', 500));
                $text = '“'.$text.'”';
            }
            if ($mode === 'deidentified') {
                $text = $this->deidentifier->redact($text, $names);
            }
            $items[] = ['ref' => (string) $item['ref'], 'text' => $text, 'section' => (string) ($item['section'] ?? 'Facts'), 'kind' => (string) ($item['kind'] ?? 'fact'), 'untrusted' => ! empty($item['untrusted']), 'placeholder' => ! empty($item['placeholder'])];
        }
        if ($score >= $this->policy->int('injection_block_score', 3) && $uc->risk_class !== 'low') {
            $this->block($call, $uc, $provider, $model, __('The text contains instructions aimed at the AI, so this request was refused.'), $score);
        }
        if (mb_strlen((string) json_encode($items, JSON_UNESCAPED_UNICODE)) > $this->policy->int('context.max_chars', 12000)) {
            $items = $this->trim($items);
        }

        [$instruction, $promptVersion] = $this->prompts->instruction($call->organizationId, $call->useCase, $call->language);
        $started = microtime(true);
        try {
            $response = $this->providers->for($provider, $model)->complete(new ProviderRequest($call->task, $instruction, $items, $call->language));
        } catch (Throwable $e) {
            $this->record($call, $uc, $provider, $model, $promptVersion, $mode, 'failed', 'The provider failed: '.mb_substr($e->getMessage(), 0, 200), $score, 0, $items, (int) round((microtime(true) - $started) * 1000));
            throw new AiBlocked(__('The AI service could not answer. Nothing was changed.'));
        }
        $latency = (int) round((microtime(true) - $started) * 1000);
        $checked = $this->output->filter($response, $items);
        $request = $this->record($call, $uc, $provider, $model, $promptVersion, $mode, 'completed', null, $score, $checked['suppressed'], $items, $latency);

        if (($checked['reasons']['action'] ?? 0) > 0) {
            $this->incidents->open($call->organizationId, 'unsafe_output', 'low', $call->useCase, null, __(':n line(s) that told someone to act were removed from an AI answer.', ['n' => $checked['reasons']['action']]));
        }
        if ($score > 0) {
            $this->incidents->open($call->organizationId, 'injection', $score >= 3 ? 'high' : 'low', $call->useCase, null, __('Text with instruction-like content (:n signs) was found in what the AI was given; it was quoted as data.', ['n' => $score]));
        }

        $none = $checked['lines'] === [] || collect($checked['lines'])->every(fn ($l) => ! empty($l['placeholder']));
        $sources = collect($items)->filter(fn ($i) => collect($checked['lines'])->contains(fn ($l) => in_array($i['ref'], $l['refs'], true)))->map(fn ($i) => ['ref' => $i['ref'], 'label' => mb_substr($i['text'], 0, 90)])->values()->all();

        return AiOutput::create(['organization_id' => $call->organizationId, 'request_id' => $request->id, 'use_case_code' => $call->useCase, 'patient_id' => $call->patientId, 'kind' => $none ? 'none' : $call->kind,
            'title' => mb_substr($call->title, 0, 200), 'text' => $none ? __('No supported answer: nothing in the records supports a statement here.') : $this->render($checked['lines']), 'lines' => $checked['lines'], 'sources' => $sources,
            'label' => (string) $this->policy->get('output.label', 'AI-generated — needs review'), 'uncertainty' => $call->uncertainty, 'suppressed' => $checked['suppressed'], 'status' => 'draft',
            'expires_at' => now()->addHours($this->policy->int('output.expire_hours', 72)), 'requested_by' => $call->user->id]);
    }

    private function check(AiCall $call, ?AiUseCase $uc): void
    {
        if (! $this->policy->bool('enabled', true)) {
            $this->block($call, $uc, null, null, __('AI is switched off.'));
        }
        if (! $uc || $uc->status !== 'enabled' || ! $uc->implemented) {
            $this->block($call, $uc, null, null, __('This AI function is not switched on.'));
        }
        if (! $call->user->can('ai.use')) {
            $this->block($call, $uc, null, null, __('You are not allowed to use AI functions.'));
        }
        $provider = $uc->provider;
        $model = $uc->model;
        if (! $provider || ! $model || $provider->status !== 'enabled' || $model->status !== 'approved' || $model->provider_id !== $provider->id) {
            $this->block($call, $uc, $provider, $model, __('No approved model is available for this function.'));
        }
    }

    /** identifiable | deidentified: abroad, or without a recorded legal approval, only de-identified facts leave. */
    public function mode(AiProvider $provider): string
    {
        $external = in_array($provider->location, $this->policy->list('provider.external_locations'), true);
        if ($provider->allows_identifiable && (! $external || ($provider->legal_ref && $provider->legal_approved_by))) {
            return 'identifiable';
        }

        return $external || $provider->kind === 'http' ? 'deidentified' : 'identifiable';
    }

    /** @return never */
    private function block(AiCall $call, ?AiUseCase $uc, ?AiProvider $provider, ?AiModel $model, string $reason, int $score = 0): void
    {
        AiRequest::create(['organization_id' => $call->organizationId, 'use_case_code' => $call->useCase, 'user_id' => $call->user->id, 'patient_id' => $call->patientId, 'provider_id' => $provider?->id, 'model_name' => $model?->name,
            'model_version' => $model?->version, 'task' => $call->task, 'outcome' => 'blocked', 'blocked_reason' => mb_substr($reason, 0, 300), 'injection_score' => $score, 'input' => null]);
        throw new AiBlocked($reason);
    }

    /** @param list<array<string,mixed>> $items */
    private function record(AiCall $call, AiUseCase $uc, AiProvider $provider, AiModel $model, ?int $promptVersion, string $mode, string $outcome, ?string $why, int $score, int $suppressed, array $items, int $latency): AiRequest
    {
        return AiRequest::create(['organization_id' => $call->organizationId, 'use_case_code' => $call->useCase, 'user_id' => $call->user->id, 'patient_id' => $call->patientId, 'provider_id' => $provider->id, 'model_name' => $model->name, 'model_version' => $model->version,
            'prompt_version' => $promptVersion, 'task' => $call->task, 'mode' => $mode, 'outcome' => $outcome, 'blocked_reason' => $why, 'injection_score' => $score, 'suppressed' => $suppressed, 'input' => $items, 'latency_ms' => $latency]);
    }

    /** Oldest facts go first when there is too much. @param list<array<string,mixed>> $items @return list<array<string,mixed>> */
    private function trim(array $items): array
    {
        $max = $this->policy->int('context.max_chars', 12000);
        while (count($items) > 1 && mb_strlen((string) json_encode($items, JSON_UNESCAPED_UNICODE)) > $max) {
            array_pop($items);
        }

        return $items;
    }

    /** @param list<array{text:string,refs:list<string>,section:string}> $lines */
    private function render(array $lines): string
    {
        $out = [];
        foreach (collect($lines)->groupBy('section') as $section => $group) {
            $out[] = '**'.$section.'**';
            foreach ($group as $l) {
                $out[] = '- '.$l['text'].($l['refs'] ? ' ['.implode(', ', $l['refs']).']' : '');
            }
            $out[] = '';
        }

        return trim(implode("\n", $out));
    }
}
