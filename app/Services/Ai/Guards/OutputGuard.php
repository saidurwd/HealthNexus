<?php

namespace App\Services\Ai\Guards;

use App\Services\Ai\Providers\ProviderResponse;

/**
 * Every line a provider returns is checked before anyone sees it. A line is dropped when it tells someone to act (prescribe, order, discharge, change a dose…, in English or Bangla), when it cites nothing or cites a
 * fact that is not in this request, or when it states a number that is not in the facts it cites. What is left is the draft; the number dropped is kept so the reviewer and the monitors know.
 */
class OutputGuard
{
    /**
     * @param  list<array{ref:string,text:string}>  $items
     * @return array{lines:list<array{text:string,refs:list<string>,section:string,placeholder?:bool}>,suppressed:int,reasons:array<string,int>}
     */
    public function filter(ProviderResponse $response, array $items): array
    {
        $byRef = [];
        foreach ($items as $i) {
            $byRef[$i['ref']] = (string) $i['text'];
        }
        $kept = [];
        $reasons = [];
        $drop = function (string $why) use (&$reasons) {
            $reasons[$why] = ($reasons[$why] ?? 0) + 1;
        };
        foreach ($response->lines as $line) {
            $text = trim(strip_tags((string) $line['text']));
            $refs = array_values(array_unique(array_map('strval', $line['refs'])));
            if ($text === '') {
                continue;
            }
            if ($this->isAction($text)) {
                $drop('action');

                continue;
            }
            if (! empty($line['placeholder'])) {
                $kept[] = ['text' => $text, 'refs' => [], 'section' => (string) ($line['section'] ?? 'Draft'), 'placeholder' => true];

                continue;
            }
            if ($refs === [] || count(array_diff($refs, array_keys($byRef))) > 0) {
                $drop($refs === [] ? 'no_source' : 'foreign_source');

                continue;
            }
            $sourceText = implode(' ', array_map(fn ($r) => $byRef[$r], $refs));
            if ($this->hasUnsupportedNumber($text, $sourceText)) {
                $drop('number_not_in_source');

                continue;
            }
            $kept[] = ['text' => preg_replace('~https?://\S+~iu', '[link removed]', $text) ?? $text, 'refs' => $refs, 'section' => (string) ($line['section'] ?? 'Facts')];
        }

        return ['lines' => $kept, 'suppressed' => array_sum($reasons), 'reasons' => $reasons];
    }

    public function isAction(string $text): bool
    {
        $t = mb_strtolower($text);
        foreach ((array) config('ai.forbidden_actions', []) as $phrase) {
            if ($phrase !== '' && str_contains($t, mb_strtolower($phrase))) {
                return true;
            }
        }

        return false;
    }

    /** A number in the line that the cited facts do not contain. Numbers in dates and references are not counted. */
    private function hasUnsupportedNumber(string $line, string $source): bool
    {
        $norm = fn (string $s) => strtr($s, ['০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4', '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9']);
        preg_match_all('/\d+(?:[.,]\d+)?/u', $norm($line), $mine);
        preg_match_all('/\d+(?:[.,]\d+)?/u', $norm($source), $theirs);
        $allowed = array_flip($theirs[0]);
        foreach ($mine[0] as $n) {
            if (! isset($allowed[$n])) {
                return true;
            }
        }

        return false;
    }
}
