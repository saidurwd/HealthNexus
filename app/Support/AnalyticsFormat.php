<?php

namespace App\Support;

/** How a figure is written on an analytics screen. */
class AnalyticsFormat
{
    public static function value(int|float|string|null $v, string $unit): string
    {
        if ($v === null) {
            return '—';
        }
        $v = (float) $v;

        return match ($unit) {
            'count', 'money' => number_format($v, 0),
            'percent' => number_format($v, 1).'%',
            'minutes' => number_format($v, 0).' '.__('min'),
            'hours' => number_format($v, 1).' '.__('h'),
            'days' => number_format($v, 1).' '.__('days'),
            default => number_format($v, 2),
        };
    }
}
