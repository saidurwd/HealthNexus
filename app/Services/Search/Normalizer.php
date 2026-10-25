<?php

namespace App\Services\Search;

use Normalizer as IntlNormalizer;

/** Canonical forms used by both the indexer and the query side, so they always agree. */
final class Normalizer
{
    /** Bangladeshi mobile in any format -> national form without country code or trunk zero: 01712345678, +8801712345678, 880 1712-345678 -> 1712345678 */
    public static function phone(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', self::latinDigits((string) $value));

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '880')) {
            $digits = substr($digits, 3);
        }

        return ltrim($digits, '0') ?: null;
    }

    /** Bangla digits to ASCII, so "০১৭১২৩৪৫৬৭৮" matches "01712345678". */
    public static function latinDigits(string $value): string
    {
        return strtr($value, ['০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4', '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9']);
    }

    /** Lower-case, Unicode-composed, joiners removed, accents stripped from Latin letters. @return list<string> */
    public static function nameTokens(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        $value = class_exists(IntlNormalizer::class) ? (IntlNormalizer::normalize($value, IntlNormalizer::FORM_C) ?: $value) : $value;
        $value = str_replace(["\u{200C}", "\u{200D}"], '', mb_strtolower($value));
        $value = preg_replace('/[^\p{L}\p{M}\p{N}\s]+/u', ' ', $value);

        return array_values(array_unique(array_filter(preg_split('/\s+/u', trim($value)), fn ($t) => mb_strlen($t) >= 1)));
    }

    /** Sound-alike key for Latin-script tokens (rahim ~ rahem, mohammad ~ muhammad). Null for non-Latin tokens. */
    public static function phonetic(string $token): ?string
    {
        if (! preg_match('/^[a-z]+$/', $token) || strlen($token) < 3) {
            return null;
        }

        return metaphone($token) ?: null;
    }

    /** MRN / identifier: upper-case letters and digits only (DHC-00000123 and dhc00000123 are the same). */
    public static function code(?string $value): ?string
    {
        $value = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', self::latinDigits((string) $value)));

        return $value === '' ? null : $value;
    }
}
