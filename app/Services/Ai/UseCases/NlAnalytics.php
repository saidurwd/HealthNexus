<?php

namespace App\Services\Ai\UseCases;

use App\Models\Ai\AiOutput;
use App\Models\User;
use App\Services\Ai\AiCall;
use App\Services\Ai\AiGateway;
use App\Services\Analytics\Insight\QuestionService;
use App\Services\Analytics\Semantic\ScopeContext;
use Illuminate\Validation\ValidationException;

/** Use case 4: a plain question about the hospital's figures, turned into a query that is shown, then run with the person's own rights (their hospital, their branches, small numbers hidden). */
class NlAnalytics
{
    public function __construct(private readonly QuestionService $questions, private readonly AiGateway $gateway) {}

    public function ask(User $user, string $question, ?int $organizationId = null): AiOutput
    {
        if (! $user->can('analytics.question.ask')) {
            abort(403);
        }
        $org = $organizationId ?? (int) $user->organizations()->value('organizations.id');
        $read = $this->questions->ask($question, ScopeContext::for($user, $org));
        $items = [];
        if (! $read['understood'] || $read['query'] === null || $read['result'] === null) {
            throw ValidationException::withMessages(['question' => $read['message']]);
        }
        $q = $read['query'];
        $items[] = ['ref' => 'q:read', 'text' => $read['message'], 'section' => __('How the question was read'), 'kind' => 'query'];
        $items[] = ['ref' => 'q:query', 'text' => __('Query: :f; figures :m; by :g; from :a to :b.', ['f' => $q['fact'], 'm' => implode(', ', $q['measures']), 'g' => $q['group_by'] ? implode(' and ', $q['group_by']) : ($q['grain'] ?? __('total')), 'a' => $q['from'] ?? '—', 'b' => $q['to'] ?? '—']), 'section' => __('How the question was read'), 'kind' => 'query'];
        $cols = collect($read['result']['columns'])->pluck('label')->all();
        foreach (array_slice($read['result']['rows'], 0, 25) as $n => $row) {
            $items[] = ['ref' => 'q:row'.($n + 1), 'text' => implode('; ', collect(array_values((array) $row))->map(fn ($v, $i) => ($cols[$i] ?? '').': '.(is_numeric($v) ? (float) $v : (string) $v))->all()), 'section' => __('Result'), 'kind' => 'row'];
        }

        return $this->gateway->run(new AiCall($org, $user, 'nl_analytics', 'query', $items, 'query', __('Answer to: :q', ['q' => mb_substr($question, 0, 120)]), null, 'en', __('Figures come from the warehouse, with your own access rights applied. Small numbers may be hidden.'), [$question]));
    }
}
