<?php

namespace App\Services\Ai;

use App\Models\Ai\AiPrompt;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/** Prompts are versioned: a change is a new version that is activated on purpose, and every request records the version it used. */
class PromptRegistry
{
    public const DEFAULTS = [
        'timeline_summary' => 'Summarise the patient\'s record from the facts. State only what the facts state, group them, and cite each line.',
        'note_draft' => 'Lay out a draft clinical note from the facts of this visit. Leave the assessment and plan for the clinician. Cite each line.',
        'knowledge_answer' => 'Answer the question using only the passages. Quote them and cite them. If they do not answer it, say so.',
        'inventory_forecast' => 'Present the forecast facts clearly, with the range and the back-test error. Add nothing.',
        'nl_analytics' => 'Present the figures from the query result. Add nothing.',
    ];

    /** @return array{0:string,1:?int} the instruction and the version used (null = the built-in text) */
    public function instruction(int $organizationId, string $useCase, string $language): array
    {
        $prompt = AiPrompt::query()->where(['organization_id' => $organizationId, 'use_case_code' => $useCase, 'status' => 'active'])->orderByDesc('version')->first();
        $text = $prompt ? $prompt->template : (self::DEFAULTS[$useCase] ?? 'Present the facts. Add nothing.');

        return [str_replace('{language}', $language, $text), $prompt?->version];
    }

    public function draft(int $organizationId, string $useCase, string $template, User $by): AiPrompt
    {
        if (mb_strlen(trim($template)) < 20) {
            throw ValidationException::withMessages(['template' => __('Write the instruction.')]);
        }
        $next = ((int) AiPrompt::query()->where(['organization_id' => $organizationId, 'use_case_code' => $useCase])->max('version')) + 1;

        return AiPrompt::create(['organization_id' => $organizationId, 'use_case_code' => $useCase, 'version' => $next, 'template' => mb_substr($template, 0, 4000), 'status' => 'draft', 'created_by' => $by->id]);
    }

    /** Activate a version (another person than its author); the one that was active is retired. */
    public function activate(AiPrompt $prompt, User $by): AiPrompt
    {
        if ($prompt->status !== 'draft') {
            throw ValidationException::withMessages(['status' => __('Only a draft can be activated.')]);
        }
        if ($prompt->created_by === $by->id) {
            throw ValidationException::withMessages(['status' => __('A second person must approve a prompt.')]);
        }
        AiPrompt::query()->where(['organization_id' => $prompt->organization_id, 'use_case_code' => $prompt->use_case_code, 'status' => 'active'])->update(['status' => 'retired']);
        $prompt->update(['status' => 'active', 'approved_by' => $by->id]);

        return $prompt;
    }
}
