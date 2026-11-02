<?php

namespace App\Support;

use App\Support\Dashboard\Numbers;

/** How a figure is written on an analytics screen. */
class AnalyticsFormat
{
    public static function value(int|float|string|null $v, string $unit): string
    {
        return Numbers::value($v, $unit);
    }
}
