<?php

namespace App\Interop\Fhir;

/** FHIR's way of saying "no" (guideline override 1): errors on /fhir are always an OperationOutcome. */
final class OperationOutcome
{
    /** @param list<array{severity:string,code:string,diagnostics:string,expression?:list<string>}> $issues */
    public static function make(array $issues): array
    {
        return ['resourceType' => 'OperationOutcome', 'issue' => array_map(fn ($i) => array_filter(['severity' => $i['severity'], 'code' => $i['code'], 'details' => ['text' => $i['diagnostics']], 'diagnostics' => $i['diagnostics'], 'expression' => $i['expression'] ?? null]), $issues)];
    }

    public static function error(string $code, string $text, string $severity = 'error', array $expression = []): array
    {
        return self::make([['severity' => $severity, 'code' => $code, 'diagnostics' => $text] + ($expression ? ['expression' => $expression] : [])]);
    }

    public static function ok(string $text = 'All OK'): array
    {
        return self::make([['severity' => 'information', 'code' => 'informational', 'diagnostics' => $text]]);
    }
}
