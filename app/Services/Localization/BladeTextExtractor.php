<?php

namespace App\Services\Localization;

/**
 * Finds user-visible English text in Blade views and wraps it in __('...') so it can be translated through lang/bn.json
 * (Laravel's source-string translation: the English text is the key, a missing translation falls back to the English).
 *
 * Deliberately conservative: it only touches text that sits alone between two tags, and a few static attributes
 * (placeholder, title, alt, aria-label). Text that mixes with {{ }}, @directives or entities is reported, never rewritten.
 */
class BladeTextExtractor
{
    private const CODE_OPEN = "\u{E004}";

    private const CODE_CLOSE = "\u{E005}";

    private const BLOCK_OPEN = "\u{E000}";

    private const BLOCK_CLOSE = "\u{E001}";

    private const COMMENT_OPEN = "\u{E002}";

    private const COMMENT_CLOSE = "\u{E003}";

    /** @return array{content: string, keys: list<string>, skipped: list<string>} */
    public function process(string $content): array
    {
        $keys = $skipped = [];

        // Hide everything that must never be treated as markup or text, behind placeholders; restore it at the end.
        $hidden = [];
        $content = $this->mask('#<(script|style|pre|code|textarea)\b.*?</\1>#is', $content, $hidden, self::BLOCK_OPEN, self::BLOCK_CLOSE);

        $comments = [];
        $content = $this->mask('#\{\{--.*?--\}\}|<!--.*?-->#s', $content, $comments, self::COMMENT_OPEN, self::COMMENT_CLOSE);

        $code = [];
        $content = $this->mask('#@php\b.*?@endphp|<\?php.*?\?>|\{\{.*?\}\}|\{!!.*?!!\}#s', $content, $code, self::CODE_OPEN, self::CODE_CLOSE);
        $content = $this->maskDirectives($content, $code);

        // 1. text between tags
        $content = preg_replace_callback('#>([^<>]+)<#', function ($m) use (&$keys, &$skipped) {
            $raw = $m[1];
            $text = trim($raw);

            if (! $this->translatable($text)) {
                return $m[0];
            }

            if (preg_match('/[{}@]|&[a-z#0-9]+;|[\x{E000}-\x{E005}]/u', $text)) {
                $skipped[] = preg_replace('/\s+/', ' ', $text);

                return $m[0];
            }

            $key = $this->key($text);
            $keys[] = $key;
            $lead = substr($raw, 0, strlen($raw) - strlen(ltrim($raw)));
            $trail = substr($raw, strlen(rtrim($raw)));

            return '>'.$lead."{{ __('".$this->escape($key)."') }}".$trail.'<';
        }, $content);

        // 2. static attributes
        $content = preg_replace_callback('#\b(placeholder|title|alt|aria-label)="([^"{}@<>]+)"#', function ($m) use (&$keys) {
            $text = trim($m[2]);
            if (! $this->translatable($text) || str_contains($text, '&') || preg_match('/[\x{E000}-\x{E005}]/u', $text)) {
                return $m[0];
            }
            $key = $this->key($text);
            $keys[] = $key;

            return $m[1].'="{{ __(\''.$this->escape($key).'\') }}"';
        }, $content);

        $content = $this->unmask($content, $code, self::CODE_OPEN, self::CODE_CLOSE);
        $content = $this->unmask($content, $comments, self::COMMENT_OPEN, self::COMMENT_CLOSE);
        $content = $this->unmask($content, $hidden, self::BLOCK_OPEN, self::BLOCK_CLOSE);

        return ['content' => $content, 'keys' => $keys, 'skipped' => $skipped];
    }

    private function mask(string $pattern, string $content, array &$store, string $open, string $close): string
    {
        return preg_replace_callback($pattern, function ($m) use (&$store, $open, $close) {
            $store[] = $m[0];

            return $open.(count($store) - 1).$close;
        }, $content);
    }

    private function unmask(string $content, array $store, string $open, string $close): string
    {
        // Placeholders can nest (a directive inside a block), so repeat until none is left.
        $pattern = '#'.preg_quote($open, '#').'(\d+)'.preg_quote($close, '#').'#u';

        return preg_replace_callback($pattern, fn ($m) => $store[(int) $m[1]], $content);
    }

    /** Replaces `@name(` ... matching `)` with a placeholder so expressions such as ->get() never look like markup. */
    private function maskDirectives(string $content, array &$code): string
    {
        $out = '';
        $length = strlen($content);
        $i = 0;

        while ($i < $length) {
            if ($content[$i] === '@' && preg_match('/@\w+[ \t]*\(/A', $content, $m, 0, $i)) {
                $start = $i;
                $i += strlen($m[0]);
                $depth = 1;

                while ($i < $length && $depth > 0) {
                    $char = $content[$i];

                    if ($char === "'" || $char === '"') {
                        $i++;
                        while ($i < $length && $content[$i] !== $char) {
                            $i += $content[$i] === '\\' ? 2 : 1;
                        }
                    } elseif ($char === '(') {
                        $depth++;
                    } elseif ($char === ')') {
                        $depth--;
                    }

                    $i++;
                }

                $code[] = substr($content, $start, $i - $start);
                $out .= self::CODE_OPEN.(count($code) - 1).self::CODE_CLOSE;

                continue;
            }

            $out .= $content[$i++];
        }

        return $out;
    }

    private function translatable(string $text): bool
    {
        return $text !== '' && preg_match('/[A-Za-z]{2,}/', $text) === 1 && ! preg_match('/^[\w.\-]+@[\w.\-]+$/', $text);
    }

    private function key(string $text): string
    {
        return preg_replace('/\s+/', ' ', $text);
    }

    private function escape(string $key): string
    {
        return str_replace(['\\', "'"], ['\\\\', "\\'"], $key);
    }
}
