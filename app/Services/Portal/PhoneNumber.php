<?php

namespace App\Services\Portal;

/** Bangladeshi mobile numbers compared as digits with the country code first: 01712-345678, +880 1712 345678 and 8801712345678 are one number. */
final class PhoneNumber
{
    public static function normalize(?string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $raw);
        if ($digits === '' || $digits === null) {
            return null;
        }
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (str_starts_with($digits, '880')) {
            return strlen($digits) === 13 ? $digits : null;
        }
        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            return '88'.$digits;
        }
        if (strlen($digits) === 10 && str_starts_with($digits, '1')) {
            return '880'.$digits;
        }

        return null;
    }

    public static function same(?string $a, ?string $b): bool
    {
        $a = self::normalize($a);

        return $a !== null && $a === self::normalize($b);
    }

    /** 8801712345678 -> 01712•••678, for screens that must not show the whole number. */
    public static function mask(?string $normalized): string
    {
        if ($normalized === null || strlen($normalized) < 11) {
            return '';
        }

        return '0'.substr($normalized, 3, 4).'•••'.substr($normalized, -3);
    }

    /** Stable hash for counting attempts per number without keeping the number in the event table. */
    public static function hash(?string $normalized): ?string
    {
        return $normalized === null ? null : hash_hmac('sha256', $normalized, (string) config('app.key'));
    }
}
