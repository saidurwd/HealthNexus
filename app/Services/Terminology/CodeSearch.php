<?php

namespace App\Services\Terminology;

use App\Models\Terminology\CodeConcept;
use Illuminate\Support\Collection;

/** Picker search over the registry: exact code first, then code prefix, then display text. */
class CodeSearch
{
    /** @return Collection<int, array{system: string, code: string, display: string}> */
    public function search(string $systemKey, string $term, int $limit = 20): Collection
    {
        $term = trim($term);
        $like = addcslashes($term, '%_\\');

        return CodeConcept::query()
            ->whereHas('system', fn ($q) => $q->where('key', $systemKey))
            ->where('status', 'active')
            ->when($term !== '', fn ($q) => $q->where(fn ($q2) => $q2
                ->where('code', 'like', "{$like}%")
                ->orWhere('display_en', 'like', "%{$like}%")
                ->orWhere('display_bn', 'like', "%{$like}%")))
            ->orderByRaw('case when code = ? then 0 when code like ? then 1 else 2 end', [$term, "{$like}%"])
            ->orderBy('code')
            ->limit(min($limit, 50))
            ->get()
            ->map(fn (CodeConcept $c) => ['system' => $systemKey, 'code' => $c->code, 'display' => $c->display_en]);
    }
}
