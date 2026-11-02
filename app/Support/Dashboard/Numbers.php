<?php

namespace App\Support\Dashboard;

use App\Services\SettingsService;

/**
 * How a figure is written on a dashboard. Two choices belong to the hospital, not the screen: whether thousands are grouped the South Asian way (12,34,567, as taka are usually written) or the international way (1,234,567),
 * and whether the Bangla interface writes digits as ০১২৩. Both default to the plain choice and are changed in Settings (`ux.number_grouping`, `ux.bangla_digits`).
 */
class Numbers
{
    private const BANGLA = ['0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪', '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯'];

    public static function grouping(): string
    {
        $v = app(SettingsService::class)->get('ux.number_grouping');

        return in_array($v, ['south_asian', 'international'], true) ? $v : (string) config('ux.numbers.grouping', 'international');
    }

    public static function banglaDigits(): bool
    {
        if (app()->getLocale() !== 'bn') {
            return false;
        }
        $v = app(SettingsService::class)->get('ux.bangla_digits');

        return filter_var($v ?? config('ux.numbers.bangla_digits', false), FILTER_VALIDATE_BOOLEAN);
    }

    public static function number(int|float|string $value, int $decimals = 0): string
    {
        $v = (float) $value;
        $negative = $v < 0;
        [$int, $dec] = array_pad(explode('.', number_format(abs($v), $decimals, '.', '')), 2, null);
        if (self::grouping() === 'south_asian' && strlen($int) > 3) {
            $int = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', substr($int, 0, -3)).','.substr($int, -3);
        } else {
            $int = number_format((float) $int, 0, '.', ',');
        }
        $out = ($negative ? '-' : '').$int.($dec !== null ? '.'.$dec : '');

        return self::digits($out);
    }

    /** Write the digits of a string the way the hospital chose. */
    public static function digits(string $text): string
    {
        return self::banglaDigits() ? strtr($text, self::BANGLA) : $text;
    }

    /** @param  string  $unit  count | money | taka | percent | minutes | hours | days | ratio */
    public static function value(int|float|string|null $v, string $unit = 'count'): string
    {
        if ($v === null) {
            return '—';
        }

        return match ($unit) {
            'count' => self::number($v),
            'money' => self::number($v),
            'taka' => '৳ '.self::number($v),
            'percent' => self::number($v, 1).'%',
            'minutes' => self::number($v).' '.self::digits(__('min')),
            'hours' => self::number($v, 1).' '.self::digits(__('h')),
            'days' => self::number($v, 1).' '.self::digits(__('days')),
            default => self::number($v, 2),
        };
    }
}
