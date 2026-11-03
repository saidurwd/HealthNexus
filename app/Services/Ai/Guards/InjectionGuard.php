<?php

namespace App\Services\Ai\Guards;

/**
 * Text that did not come from the system (patient messages, notes, documents, questions) is data. This counts the signs of an attempt to give the model instructions (English and Bangla), and neutralises the
 * text so it cannot pass for markup or a tool call: angle brackets, code fences, links and control characters are defused. The words are kept, because the clinical content is still wanted.
 */
class InjectionGuard
{
    /** How many instruction-like patterns the text contains. */
    public function score(string $text): int
    {
        $text = $this->normalise($text);
        $n = 0;
        foreach ((array) config('ai.injection_patterns', []) as $p) {
            if (@preg_match('/'.str_replace('/', '\/', $p).'/iu', $text) === 1) {
                $n++;
            }
        }

        return $n;
    }

    /** Hidden characters (zero-width, direction marks) are the usual way to hide a phrase from a filter: take them out and fold look-alike spacing. */
    public function normalise(string $text): string
    {
        $text = preg_replace('/[\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2060}-\x{2064}\x{FEFF}\x{00AD}]/u', '', $text) ?? $text;
        $text = preg_replace('/[\x{00A0}\x{2000}-\x{200A}\x{3000}]/u', ' ', $text) ?? $text;

        return preg_replace('/\s+/u', ' ', $text) ?? $text;
    }

    public function neutralise(string $text, int $max = 500): string
    {
        $text = $this->normalise($text);
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? '';
        $text = preg_replace('~https?://\S+~iu', '[link removed]', $text) ?? $text;
        $text = str_replace(['<', '>', '```', '{{', '}}'], ['‹', '›', "'''", '{ {', '} }'], $text);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return mb_substr(trim($text), 0, $max);
    }
}
