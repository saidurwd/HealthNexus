<?php

namespace App\Services\Ai\UseCases;

use App\Models\Ai\AiKnowledge;
use App\Models\Ai\AiOutput;
use App\Models\User;
use App\Services\Ai\AiCall;
use App\Services\Ai\AiGateway;
use App\Services\Ai\AiPolicy;
use App\Services\Ai\Guards\InjectionGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Questions answered from what the hospital has approved: the current approved version of each policy and procedure (document control) and the knowledge items its committee approved. Passages are ranked by how
 * much of the question they cover (rarer words count more); a question the passages do not cover is answered "no supported answer", never guessed. Every passage is quoted with its document and version.
 */
class KnowledgeService
{
    private const STOP = ['the', 'a', 'an', 'is', 'are', 'what', 'how', 'when', 'where', 'who', 'do', 'does', 'should', 'to', 'of', 'in', 'for', 'and', 'or', 'on', 'we', 'i', 'it', 'be', 'our', 'my', 'can', 'with', 'at', 'by', 'about', 'this', 'that'];

    public function __construct(private readonly AiGateway $gateway, private readonly AiPolicy $policy) {}

    public function answer(User $user, string $question, ?int $organizationId = null): AiOutput
    {
        $question = trim($question);
        if (mb_strlen($question) < 5) {
            throw ValidationException::withMessages(['question' => __('Ask a question.')]);
        }
        $org = $organizationId ?? (int) $user->organizations()->value('organizations.id');
        $passages = $this->search((int) $org, $question);
        $items = collect($passages)->map(fn ($p) => ['ref' => $p['ref'], 'text' => $p['text'], 'section' => $p['label'], 'kind' => 'passage'])->all();

        return $this->gateway->run(new AiCall((int) $org, $user, 'knowledge_answer', 'answer', $items, 'answer', __('Answer to: :q', ['q' => mb_substr($question, 0, 120)]), null, 'en',
            $passages === [] ? __('Nothing in the approved policies covers this question.') : __('Quoted from approved documents. Check the document for the full text.'), [$question]));
    }

    /** @return list<array{ref:string,label:string,text:string,score:float}> */
    public function search(int $organizationId, string $question): array
    {
        $corpus = $this->corpus($organizationId);
        if ($corpus === []) {
            return [];
        }
        // a sentence that tries to instruct the AI is not part of the question
        $guard = app(InjectionGuard::class);
        $asked = collect(preg_split('/(?<=[.!?।])\s+/u', $question) ?: [])->reject(fn ($sentence) => $guard->score((string) $sentence) > 0)->implode(' ');
        $q = array_values(array_unique($this->tokens($asked)));
        if ($q === []) {
            return [];
        }
        $n = count($corpus);
        $df = [];
        foreach ($corpus as $p) {
            foreach (array_unique($p['tokens']) as $t) {
                $df[$t] = ($df[$t] ?? 0) + 1;
            }
        }
        // a word that is in no passage weighs as a word that is in one: it cannot be matched, but it must not make a good match look poor
        $idf = fn (string $t) => log(1 + ($n - max(1, $df[$t] ?? 0) + 0.5) / (max(1, $df[$t] ?? 0) + 0.5));
        $total = array_sum(array_map($idf, $q));
        $scored = [];
        foreach ($corpus as $p) {
            $hit = array_values(array_intersect($q, $p['tokens']));
            if (count($hit) < min($this->policy->int('knowledge.min_matches', 2), count($q))) {
                continue;
            }
            $score = $total > 0 ? array_sum(array_map($idf, $hit)) / $total : 0.0;
            if ($score >= $this->policy->float('knowledge.min_score', 0.3)) {
                $scored[] = ['ref' => $p['ref'], 'label' => $p['label'], 'text' => $p['text'], 'score' => round($score, 3)];
            }
        }
        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($scored, 0, $this->policy->int('knowledge.max_passages', 3));
    }

    /** @return list<array{ref:string,label:string,text:string,tokens:list<string>}> */
    private function corpus(int $organizationId): array
    {
        $out = [];
        $add = function (string $ref, string $label, string $body, string $title = '') use (&$out) {
            foreach (preg_split('/\n\s*\n/u', $body) ?: [] as $k => $para) {
                $para = trim(preg_replace('/\s+/u', ' ', $para) ?? '');
                if (mb_strlen($para) >= 20) {
                    $out[] = ['ref' => $ref.':p'.($k + 1), 'label' => $label, 'text' => mb_substr($para, 0, 450), 'tokens' => $this->tokens($para.' '.$title)];
                }
            }
        };
        if (Schema::hasTable('qa_documents')) {
            $docs = DB::table('qa_documents as d')->join('qa_document_versions as v', 'v.id', '=', 'd.current_version_id')->where('d.organization_id', $organizationId)->where('d.status', 'active')->where('v.status', 'approved')->get(['d.code', 'd.title', 'v.version', 'v.body']);
            foreach ($docs as $d) {
                $add('doc:'.$d->code.':v'.$d->version, $d->code.' v'.$d->version.' — '.$d->title, (string) $d->body, (string) $d->title);
            }
        }
        foreach (AiKnowledge::query()->where(['organization_id' => $organizationId, 'status' => 'approved'])->get() as $k) {
            $add('kb:'.$k->id, __('Knowledge: :t', ['t' => $k->title]), $k->body, $k->title);
        }

        return $out;
    }

    /** Words, lower-cased, without the common ones and with the plain English endings taken off (reporting, reports → report); Bangla words are left as they are. @return list<string> */
    private function tokens(string $text): array
    {
        preg_match_all('/[\p{L}\p{N}]+/u', mb_strtolower($text), $m);

        return array_values(array_map(fn ($t) => $this->stem($t), array_filter($m[0], fn ($t) => mb_strlen($t) > 1 && ! in_array($t, self::STOP, true))));
    }

    private function stem(string $t): string
    {
        if (! preg_match('/^[a-z]+$/', $t) || strlen($t) < 5) {
            return $t;
        }
        foreach (['ing', 'ed', 'es', 's'] as $suffix) {
            if (str_ends_with($t, $suffix) && ! str_ends_with($t, 'ss') && strlen($t) - strlen($suffix) >= 4) {
                return substr($t, 0, -strlen($suffix));
            }
        }

        return $t;
    }
}
