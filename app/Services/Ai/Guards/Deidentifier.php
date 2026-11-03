<?php

namespace App\Services\Ai\Guards;

/**
 * Takes out what identifies a person from free text before it leaves the hospital: the patient's own names, telephone numbers, e-mail addresses and long identity numbers (including Bangla digits). It is a safety net
 * on top of the context engine, which already leaves identifiers out of structured facts.
 */
class Deidentifier
{
    /** @param list<string> $names words that identify this person (name parts, relatives) */
    public function redact(string $text, array $names = []): string
    {
        foreach ($names as $name) {
            foreach (preg_split('/\s+/u', trim($name)) ?: [] as $part) {
                if (mb_strlen($part) >= 3) {
                    $text = preg_replace('/'.preg_quote($part, '/').'/iu', '[NAME]', $text) ?? $text;
                }
            }
        }
        $text = preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/iu', '[EMAIL]', $text) ?? $text;
        $text = preg_replace('/\+?[\d০-৯][\d০-৯\s\-]{7,}[\d০-৯]/u', '[PHONE]', $text) ?? $text;

        return $text;
    }

    /** Names to look for in free text. @return list<string> */
    public function namesOf(?object $patient): array
    {
        if ($patient === null) {
            return [];
        }

        return array_values(array_filter([(string) ($patient->first_name ?? ''), (string) ($patient->last_name ?? ''), (string) ($patient->middle_name ?? ''), (string) ($patient->name ?? '')], fn ($n) => trim($n) !== ''));
    }
}
