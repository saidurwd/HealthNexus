<?php

namespace App\Services\Search;

use App\Models\Patient;
use App\Models\User;
use App\Services\TenantContextResolver;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Top-bar search (guideline 14.5). Patients today; other work items arrive through ScanResolver.
 * Every query is an indexed exact or prefix match inside the caller's organization.
 */
class GlobalSearchService
{
    public function __construct(private readonly TenantContextResolver $tenant) {}

    /** @return Collection<int, array{type: string, id: string, label: string, number: ?string, matched_on: string, url: string}> */
    public function search(User $user, string $term, int $limit = 15): Collection
    {
        if (! $user->can('patients.view')) {
            throw new AuthorizationException('You are not allowed to search patients.');
        }

        $organizationId = $this->tenant->getOrganizationId() ?? abort(422, 'No active organization.');
        $term = trim($term);

        if (mb_strlen($term) < 2) {
            return collect();
        }

        $ranked = $this->ranked($organizationId, $term);
        if ($ranked === []) {
            return collect();
        }

        $patients = Patient::query()->where('organization_id', $organizationId)->whereIn('id', array_keys($ranked))->get()->keyBy('id');

        return collect($ranked)
            ->map(fn ($match, $id) => $patients->has($id) ? [
                'type' => 'patient', 'id' => $patients[$id]->public_id, 'label' => $patients[$id]->full_name,
                'number' => $patients[$id]->enterprise_patient_no, 'matched_on' => $match['kind'],
                'url' => route('admin.patients.show', $patients[$id]),
            ] : null)
            ->filter()->take($limit)->values();
    }

    /** @return array<int, array{kind: string, score: int}> patient id => best match, best first */
    private function ranked(int $organizationId, string $term): array
    {
        $found = [];
        $note = function (int $patientId, string $kind, int $score) use (&$found) {
            if (! isset($found[$patientId]) || $found[$patientId]['score'] < $score) {
                $found[$patientId] = ['kind' => $kind, 'score' => $score];
            }
        };

        $lookup = fn (string $kind, string $value, bool $prefix) => DB::table('patient_search_index')
            ->where('organization_id', $organizationId)->where('kind', $kind)
            ->when($prefix, fn ($q) => $q->where('term', 'like', addcslashes($value, '%_\\').'%'), fn ($q) => $q->where('term', $value))
            ->limit(50)->pluck('patient_id');

        $digits = preg_replace('/\D+/', '', Normalizer::latinDigits($term));
        $looksNumeric = $digits !== '' && preg_match('/^[\d\s+\-()০-৯]+$/u', $term) === 1;

        if ($looksNumeric) {
            if ($phone = Normalizer::phone($term)) {
                foreach ($lookup('phone', $phone, false) as $id) {
                    $note($id, 'phone', 100);
                }
                if (strlen($phone) >= 6) {
                    foreach ($lookup('phone', $phone, true) as $id) {
                        $note($id, 'phone', 80);
                    }
                }
            }
            foreach ($lookup('nid', $digits, false) as $id) {
                $note($id, 'nid', 95);
            }
            foreach ($lookup('mrn', ltrim($digits, '0'), false) as $id) {
                $note($id, 'mrn', 90);
            }
        }

        $code = Normalizer::code($term);
        if ($code !== null && preg_match('/[A-Za-z]/', $term) && preg_match('/\d/', $term)) {
            foreach ($lookup('mrn', $code, false) as $id) {
                $note($id, 'mrn', 100);
            }
            foreach ($lookup('nid', $code, false) as $id) {
                $note($id, 'nid', 95);
            }
        }

        if (! $looksNumeric) {
            $tokens = Normalizer::nameTokens($term);
            $byToken = [];
            foreach ($tokens as $token) {
                $ids = $lookup('name', $token, true)->all();
                if (($phonetic = Normalizer::phonetic($token)) !== null) {
                    $ids = array_merge($ids, $lookup('phonetic', $phonetic, false)->all());
                }
                $byToken[] = array_unique($ids);
            }

            if ($byToken !== []) {
                $all = array_intersect(...$byToken); // every typed word must match, in any order
                // One query for all candidates: how many of the typed words are matched exactly (not just as a prefix / sound-alike)?
                $exactCounts = $all === [] ? collect() : DB::table('patient_search_index')
                    ->whereIn('patient_id', $all)->where('kind', 'name')->whereIn('term', $tokens)
                    ->groupBy('patient_id')->selectRaw('patient_id, count(*) as n')->pluck('n', 'patient_id');
                foreach ($all as $id) {
                    $note($id, 'name', ($exactCounts[$id] ?? 0) >= count($tokens) ? 70 : 50);
                }
            }
        }

        uasort($found, fn ($a, $b) => $b['score'] <=> $a['score']);

        return $found;
    }
}
