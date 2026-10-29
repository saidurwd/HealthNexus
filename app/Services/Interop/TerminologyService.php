<?php

namespace App\Services\Interop;

use App\Models\Terminology\CodeConcept;
use App\Models\Terminology\CodeMap;
use App\Models\Terminology\CodeSystem;

/** The FHIR face of the Code System Registry (S04). It reads the registry; it keeps no terminology of its own. */
class TerminologyService
{
    /** The canonical URI for a registry key (`icd10`, `loinc` …), or the fallback when the registry does not hold it. */
    public function uri(string $key, string $fallback): string
    {
        return (string) (CodeSystem::query()->where('key', $key)->value('uri') ?? $fallback);
    }

    /** @return array{system:string,code:string,display:?string,display_bn:?string}|null */
    public function lookup(string $systemUriOrKey, string $code): ?array
    {
        $system = CodeSystem::query()->where('uri', $systemUriOrKey)->orWhere('key', $systemUriOrKey)->first();
        if ($system === null) {
            return null;
        }
        $c = CodeConcept::query()->where(['code_system_id' => $system->id, 'code' => $code])->first();

        return $c ? ['system' => $system->uri, 'code' => $c->code, 'display' => $c->display_en, 'display_bn' => $c->display_bn] : null;
    }

    /** @return list<array{system:string,code:string,relationship:string}> every mapped target of a code */
    public function translate(string $fromSystemUriOrKey, string $code, ?string $toSystemUriOrKey = null): array
    {
        $from = CodeSystem::query()->where('uri', $fromSystemUriOrKey)->orWhere('key', $fromSystemUriOrKey)->first();
        if ($from === null) {
            return [];
        }
        $to = $toSystemUriOrKey ? CodeSystem::query()->where('uri', $toSystemUriOrKey)->orWhere('key', $toSystemUriOrKey)->first() : null;

        return CodeMap::query()->where(['from_system_id' => $from->id, 'from_code' => $code])->when($to, fn ($q) => $q->where('to_system_id', $to->id))->get()
            ->map(fn (CodeMap $m) => ['system' => (string) CodeSystem::query()->whereKey($m->to_system_id)->value('uri'), 'code' => $m->to_code, 'relationship' => $m->relationship])->values()->all();
    }
}
